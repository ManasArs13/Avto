<?php

namespace App\Jobs;

use App\Models\PriceImport;
use App\Services\PriceImporter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ImportPriceFile implements ShouldQueue
{
    use Queueable;

    public int $timeout = 3600;

    public int $tries = 1;

    public function __construct(public PriceImport $import) {}

    public function handle(PriceImporter $importer): void
    {
        $importer->run($this->import);
    }
}
