<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use League\Csv\Reader;

class CatalogoCsvLoaderService
{
    protected string $table;
    protected string $path;
    protected int $headerOffset = 0;
    protected \Closure $transform;

    public function __construct(string $table, string $path, int $headerOffset, \Closure $transform)
    {
        $this->table = $table;
        $this->path = $path;
        $this->headerOffset = $headerOffset;
        $this->transform = $transform;
    }

    public function load(): void
    {
        if (!file_exists($this->path)) {
            throw new \Exception("Archivo no encontrado: {$this->path}");
        }

        $csv = Reader::createFromPath($this->path, 'r');
        $csv->setHeaderOffset($this->headerOffset);

        foreach ($csv->getRecords() as $record) {
            $row = call_user_func($this->transform, $record);

            if (!$row || !isset($row['match']) || !isset($row['data'])) {
                continue;
            }

            DB::table($this->table)->updateOrInsert($row['match'], $row['data']);
        }
    }
}
