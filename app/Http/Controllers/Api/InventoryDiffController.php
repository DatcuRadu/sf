<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryFile;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;

class InventoryDiffController extends Controller
{
    private const COL_UPC = 5;

    public function index(InventoryFile $full, InventoryFile $delta)
    {
        ini_set('memory_limit', '1G');   // ← aici exact
        $fullRows = $this->readCsv($full);
        $deltaRows = $this->readCsv($delta);

        $fullIndex = [];

        foreach ($fullRows as $row) {

            $sku = trim($row[self::COL_UPC] ?? '');

            if (!$sku) {
                continue;
            }

            $fullIndex[$sku] = $row;
        }

        $changes = [];

        foreach ($deltaRows as $row) {

            $sku = trim($row[self::COL_UPC] ?? '');

            if (!$sku) {
                continue;
            }

            if (!isset($fullIndex[$sku])) {
                continue;
            }

            $old = $fullIndex[$sku];
            $diff = $this->diff($old, $row);

            if ($diff) {

                $changes[] = [
                    'sku' => $sku,
                    'before' => $old,
                    'after' => $row,
                    'changes' => $diff
                ];
            }
        }

        return response()->json($changes);
    }

    private function readCsv(InventoryFile $inventory)
    {
        $path = $inventory->archive_path ?? $inventory->file_name;

        $stream = Storage::disk('epicor_inventory')->readStream($path);

        $csv = Reader::createFromStream($stream);
        $csv->setDelimiter(',');
        $csv->setHeaderOffset(null);

        return iterator_to_array($csv);
    }

    private function diff(array $old, array $new)
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