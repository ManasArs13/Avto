<?php

namespace App\Console\Commands;

use App\Enums\ImportStatus;
use App\Jobs\ImportPriceFile;
use App\Models\PriceImport;
use App\Models\Supplier;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('prices:import {supplier : Код поставщика} {file : Путь к CSV-файлу} {--sync : Выполнить сразу, без очереди}')]
#[Description('Загрузить прайс поставщика из CSV')]
class ImportPrice extends Command
{
    public function handle(): int
    {
        $supplier = Supplier::where('code', $this->argument('supplier'))->firstOrFail();
        $source = $this->argument('file');

        if (! is_file($source)) {
            $this->error("Файл не найден: $source");

            return self::FAILURE;
        }

        $path = 'prices/'.$supplier->code.'/'.now()->format('Ymd_His').'_'.basename($source);
        Storage::put($path, fopen($source, 'r'));

        $import = PriceImport::create([
            'supplier_id' => $supplier->id,
            'file_path' => $path,
            'status' => ImportStatus::Pending,
        ]);

        if ($this->option('sync')) {
            ImportPriceFile::dispatchSync($import);
            $import->refresh();
            $this->info("Готово: {$import->rows_imported} из {$import->rows_total}, ошибок {$import->rows_failed}.");
        } else {
            ImportPriceFile::dispatch($import);
            $this->info("Импорт #{$import->id} поставлен в очередь.");
        }

        return self::SUCCESS;
    }
}
