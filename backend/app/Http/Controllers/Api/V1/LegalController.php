<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\LegalDocument;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

/** Реквизиты продавца и документы для покупателей. */
class LegalController extends Controller
{
    /** GET /shop — реквизиты и список документов (для подвала сайта и экрана «О магазине»). */
    public function shop(): JsonResponse
    {
        return response()->json([
            'data' => [
                'details' => Setting::shop(),
                'documents' => LegalDocument::orderBy('sort')->get(['slug', 'title']),
            ],
        ]);
    }

    /** GET /legal/{slug} — текст документа в HTML с подставленными реквизитами. */
    public function document(LegalDocument $document): JsonResponse
    {
        return response()->json([
            'data' => [
                'slug' => $document->slug,
                'title' => $document->title,
                'version' => $document->version,
                'updated_at' => $document->updated_at?->toDateString(),
                'html' => $document->html(),
            ],
        ]);
    }
}
