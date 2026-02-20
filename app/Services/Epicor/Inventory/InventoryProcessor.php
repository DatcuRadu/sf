<?php

namespace App\Services\Epicor\Inventory;

use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;

class InventoryProcessor
{
    public function streamCsv(string $path): \Generator
    {
        $stream = Storage::disk('epicor_inventory')->readStream($path);

        $csv = Reader::createFromStream($stream);
        $csv->setHeaderOffset(null);

        foreach ($csv->getRecords() as $index => $record) {
            if ($index === 0) {
                continue;
            }
            yield $record;
        }
    }

    public function archiveFile(string $path): void
    {
        $disk = Storage::disk('epicor_inventory');

        $archiveDir = 'Inventory/Archive/' . now()->format('Y-m-d');

        if (!$disk->exists($archiveDir)) {
            $disk->makeDirectory($archiveDir);
        }

        $disk->move($path, $archiveDir . '/' . basename($path));
    }

    public function getLatestFile(string $prefix): ?string
    {
        return collect(Storage::disk('epicor_inventory')->files('Inventory'))
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
