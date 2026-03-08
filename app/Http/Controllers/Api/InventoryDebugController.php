<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\JsonResponse;

class InventoryDebugController extends Controller
{
    private const COL_UPC = 5;

    public function changes(int $id): JsonResponse
    {
        ini_set('memory_limit', '1G');

        $current = InventoryFile::findOrFail($id);

        // găsim fișierul anterior
        $previous = InventoryFile::query()
            ->where('status', 'completed')
            ->where('received_at', '<', $current->received_at)
            ->orderByDesc('received_at')
            ->first();

        if (!$previous) {
            return response()->json([
                'error' => 'No previous inventory file found'
            ], 404);
        }

        $oldRows = $this->readCsv($previous);
        $newRows = $this->readCsv($current);

        $index = [];

        foreach ($oldRows as $row) {

            $sku = trim($row[self::COL_UPC] ?? '');

            if (!$sku) {
                continue;
            }

            $index[$sku] = $row;
        }

        $changes = [];

        foreach ($newRows as $row) {

            $sku = trim($row[self::COL_UPC] ?? '');

            if (!$sku) {
                continue;
            }

            if (!isset($index[$sku])) {
                continue;
            }

            $old = $index[$sku];

            $diff = $this->diffRow($old, $row);

            if ($diff) {

                $changes[] = [
                    'sku' => $sku,
                    'changes' => $diff,
                    'before' => $old,
                    'after' => $row
                ];
            }
        }

        return response()->json([
            'current_file' => $current->file_name,
            'previous_file' => $previous->file_name,
            'changes' => $changes
        ]);
    }

    private function readCsv(InventoryFile $inventory): array
    {
        $path = $inventory->archive_path ?? $inventory->file_name;

        $stream = Storage::disk('epicor_inventory')->readStream($path);

        $rows = [];

        while (($row = fgetcsv($stream)) !== false) {
            $rows[] = $row;
        }

        fclose($stream);

        return $rows;
    }

    private function diffRow(array $old, array $new): array
    {
        $changes = [];

        foreach ($new as $i => $value) {

            $oldValue = $old[$i] ?? null;

            if ((string)$oldValue !== (string)$value) {

                $changes[$i] = [
                    'old' => $oldValue,
                    'new' => $value
                ];
            }
        }

        return $changes;
    }
}