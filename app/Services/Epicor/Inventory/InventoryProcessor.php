<?php

namespace App\Services\Epicor\Inventory;

use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use Illuminate\Support\Carbon;

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

    public function archiveFile(string $path)
    {
        $disk = Storage::disk('epicor_inventory');

        $archiveDir = 'Inventory/Archive/' . now()->format('Y-m-d');

        if (!$disk->exists($archiveDir)) {
            $disk->makeDirectory($archiveDir);
        }

        $originalName = basename($path);

        $newName = pathinfo($originalName, PATHINFO_FILENAME)
            . '_' . now()->format('H-i-s')
            . '.'
            . pathinfo($originalName, PATHINFO_EXTENSION);

        $new_path = $archiveDir . '/' . $newName;

        $disk->move($path, $new_path);

        return  $new_path;

    }

    public function getLatestFile(string $prefix): ?string
    {
        $disk = Storage::disk('epicor_inventory');

        return collect($disk->files('Inventory'))
            ->filter(fn ($file) =>
                str_contains($file, $prefix) &&
                str_ends_with($file, '.csv')
            )
            ->sortByDesc(fn ($file) =>
            $disk->lastModified($file)
            )
            ->first();
    }

    public function getLastModifiedAt(string $file): ?Carbon
    {
        $disk = Storage::disk('epicor_inventory');

        if (! $disk->exists($file)) {
            return null;
        }

        return Carbon::createFromTimestamp(
            $disk->lastModified($file)
        );
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

    public function moveToBufferAndRename(string $file): ?string
    {
        $disk = Storage::disk('epicor_inventory');

        if (! $disk->exists($file)) {
            return null;
        }

        $now = Carbon::now()->format('Ymd-His');
        // exemplu: 20260227_191530

        $filename = pathinfo($file, PATHINFO_FILENAME);
        $extension = pathinfo($file, PATHINFO_EXTENSION);

        $newName = $filename . '_' . $now . '.' . $extension;

        $newPath = 'Buffer/' . $newName;

        // creează folder dacă nu există
        if (! $disk->exists('Buffer')) {
            $disk->makeDirectory('Buffer', 0775, true);
        }

        $disk->move($file, $newPath);

        return $newPath;
    }
}
