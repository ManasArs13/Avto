<?php

namespace App\Models;

use App\Enums\ConsentType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;

/** Запись журнала согласий. Записи не меняются: отзыв — новая запись с granted = false. */
#[Fillable(['customer_id', 'order_id', 'type', 'granted', 'document_slug', 'document_version', 'ip', 'user_agent'])]
class Consent extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return [
            'type' => ConsentType::class,
            'granted' => 'boolean',
        ];
    }

    public static function record(ConsentType $type, bool $granted, Request $request, ?Customer $customer = null, ?Order $order = null): self
    {
        $slug = $type->documentSlug();

        return static::create([
            'customer_id' => $customer?->id,
            'order_id' => $order?->id,
            'type' => $type,
            'granted' => $granted,
            'document_slug' => $slug,
            'document_version' => LegalDocument::currentVersion($slug),
            'ip' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
        ]);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
