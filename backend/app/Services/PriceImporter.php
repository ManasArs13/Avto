<?php

namespace App\Services;

use App\Enums\ImportStatus;
use App\Models\Brand;
use App\Models\BrandAlias;
use App\Models\Offer;
use App\Models\PriceImport;
use App\Models\Product;
use App\Support\Article;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use SplFileObject;
use Throwable;

/**
 * Загрузка CSV-прайса поставщика.
 *
 * Работает пачками через insertOrIgnore/upsert: прайс на сотни тысяч строк
 * так грузится за минуты, а не часы, как при построчном Eloquent.
 *
 * Настройки поставщика (suppliers.settings):
 *   delimiter     — разделитель, по умолчанию ";"
 *   encoding      — кодировка файла, по умолчанию "UTF-8" (часто "Windows-1251")
 *   skip_rows     — сколько строк заголовка пропустить, по умолчанию 1
 *   columns       — номера колонок, см. Supplier::DEFAULT_COLUMNS
 *   create_brands — создавать неизвестные бренды, по умолчанию true
 */
class PriceImporter
{
    private const CHUNK = 1000;

    /** @var array<string, int> нормализованный алиас => brand_id */
    private array $brandMap = [];

    public function run(PriceImport $import): void
    {
        $supplier = $import->supplier;
        $settings = $supplier->settings ?? [];
        $columns = $supplier->priceColumns();
        $startedAt = Carbon::now();

        $import->update(['status' => ImportStatus::Processing, 'started_at' => $startedAt, 'error' => null]);

        $this->brandMap = BrandAlias::pluck('brand_id', 'alias')->all();

        try {
            $file = new SplFileObject(Storage::path($import->file_path));
            $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::READ_AHEAD);
            $file->setCsvControl($settings['delimiter'] ?? ';', '"', '');

            $encoding = $settings['encoding'] ?? 'UTF-8';
            $skip = (int) ($settings['skip_rows'] ?? 1);
            $createBrands = (bool) ($settings['create_brands'] ?? true);

            $total = $imported = $failed = 0;
            $chunk = [];

            foreach ($file as $index => $row) {
                if ($index < $skip || $row === [null]) {
                    continue;
                }
                $total++;

                if ($encoding !== 'UTF-8') {
                    $row = array_map(fn ($v) => mb_convert_encoding((string) $v, 'UTF-8', $encoding), $row);
                }

                $parsed = $this->parseRow($row, $columns, $createBrands);
                if ($parsed === null) {
                    $failed++;

                    continue;
                }
                $chunk[] = $parsed;

                if (count($chunk) >= self::CHUNK) {
                    $imported += $this->flush($chunk, $supplier->id);
                    $chunk = [];
                    $import->update(['rows_total' => $total, 'rows_imported' => $imported, 'rows_failed' => $failed]);
                }
            }
            $imported += $this->flush($chunk, $supplier->id);

            // Позиции, которых нет в новом прайсе, больше не продаём.
            Offer::where('supplier_id', $supplier->id)->where('updated_at', '<', $startedAt)->delete();

            $import->update([
                'status' => ImportStatus::Done,
                'rows_total' => $total,
                'rows_imported' => $imported,
                'rows_failed' => $failed,
                'finished_at' => now(),
            ]);
        } catch (Throwable $e) {
            $import->update(['status' => ImportStatus::Failed, 'error' => $e->getMessage(), 'finished_at' => now()]);

            throw $e;
        }
    }

    private function parseRow(array $row, array $columns, bool $createBrands): ?array
    {
        $get = fn (string $key) => trim((string) ($row[$columns[$key]] ?? ''));

        $article = $get('article');
        $normalized = Article::normalize($article);
        $price = (float) str_replace([' ', ','], ['', '.'], $get('price'));

        if ($normalized === '' || $price <= 0) {
            return null;
        }

        // Бренд ищем последним, чтобы битые строки не плодили новые бренды.
        $brandId = $this->resolveBrand($get('brand'), $createBrands);
        if ($brandId === null) {
            return null;
        }

        return [
            'brand_id' => $brandId,
            'article' => $article,
            'article_normalized' => $normalized,
            'name' => $get('name') ?: $article,
            'price' => $price,
            // "> 10", "10+" и т.п. — берём только цифры.
            'quantity' => (int) preg_replace('/\D/', '', $get('quantity')),
            'delivery_days' => (int) preg_replace('/\D/', '', $get('delivery_days')),
        ];
    }

    private function resolveBrand(string $name, bool $create): ?int
    {
        $alias = Article::normalizeBrand($name);
        if ($alias === '') {
            return null;
        }

        if (! isset($this->brandMap[$alias]) && $create) {
            $brand = Brand::create(['name' => mb_strtoupper($name)]);
            $this->brandMap[$alias] = $brand->id;
        }

        return $this->brandMap[$alias] ?? null;
    }

    /**
     * @return int сколько предложений записано
     */
    private function flush(array $rows, int $supplierId): int
    {
        if ($rows === []) {
            return 0;
        }

        // Дубли внутри пачки: оставляем самую дешёвую строку.
        $unique = [];
        foreach ($rows as $row) {
            $key = $row['brand_id'].'|'.$row['article_normalized'];
            if (! isset($unique[$key]) || $row['price'] < $unique[$key]['price']) {
                $unique[$key] = $row;
            }
        }

        $now = now();

        // Новые товары создаём, существующие не трогаем (их карточки правят в админке).
        Product::insertOrIgnore(array_map(fn ($r) => [
            'brand_id' => $r['brand_id'],
            'article' => $r['article'],
            'article_normalized' => $r['article_normalized'],
            'name' => mb_substr($r['name'], 0, 255),
            'created_at' => $now,
            'updated_at' => $now,
        ], array_values($unique)));

        $products = Product::query()
            ->whereIn('article_normalized', array_column($unique, 'article_normalized'))
            ->get(['id', 'brand_id', 'article_normalized', 'created_at'])
            ->keyBy(fn ($p) => $p->brand_id.'|'.$p->article_normalized);

        $offers = [];
        foreach ($unique as $key => $row) {
            if ($product = $products->get($key)) {
                $offers[] = [
                    'supplier_id' => $supplierId,
                    'product_id' => $product->id,
                    'purchase_price' => $row['price'],
                    'quantity' => $row['quantity'],
                    'delivery_days' => $row['delivery_days'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        Offer::upsert($offers, ['supplier_id', 'product_id'], ['purchase_price', 'quantity', 'delivery_days', 'updated_at']);

        // В поисковый индекс отправляем только новые товары.
        $newIds = $products->filter(fn ($p) => $p->created_at->gte($now))->pluck('id');
        if ($newIds->isNotEmpty()) {
            Product::whereIn('id', $newIds)->searchable();
        }

        return count($offers);
    }
}
