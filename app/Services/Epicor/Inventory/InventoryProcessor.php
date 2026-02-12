<?php

namespace App\Services\Epicor\Inventory;

use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;

class InventoryProcessor
{
    protected function streamCsv(string $path): \Generator
    {
        $stream = Storage::disk('epicore')->readStream($path);

        $csv = Reader::createFromStream($stream);
        $csv->setHeaderOffset(0);

        foreach ($csv->getRecords() as $record) {
            yield $record;
        }
    }

    protected function archiveFile(string $path): void
    {
        $disk = Storage::disk('epicore');

        $archiveDir = 'Inventory/Archive/' . now()->format('Y-m-d');

        if (!$disk->exists($archiveDir)) {
            $disk->makeDirectory($archiveDir);
        }

        $disk->move($path, $archiveDir . '/' . basename($path));
    }

    protected function getLatestFile(string $prefix): ?string
    {
        return collect(Storage::disk('epicore')->files('Inventory'))
            ->filter(fn($file) => str_contains($file, $prefix))
            ->sortDesc()
            ->first();
    }

    // ===============================
    // DELTA
    // ===============================
    public function processDelta(): array
    {
        $file = $this->getLatestFile('VM_Inv_Delta_');

        if (!$file) {
            return ['message' => 'No Delta file found'];
        }

        foreach ($this->streamCsv($file) as $row) {
            // 🔥 Aici pui update stock logic
        }

        $this->archiveFile($file);

        return ['processed_delta' => $file];
    }

    // ===============================
    // FULL
    // ===============================
    public function processFull(): array
    {
        $file = $this->getLatestFile('VM_Full_Inventory_File_');

        if (!$file) {
            return ['message' => 'No Full file found'];
        }

        foreach ($this->streamCsv($file) as $row) {
            // 🔥 Aici pui full rebuild logic
        }

        $this->archiveFile($file);

        return ['processed_full' => $file];
    }
}
