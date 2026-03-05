<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use League\Csv\Reader;
use League\Csv\Statement;

class CsvController extends Controller
{
    public function index(InventoryFile $inventory, Request $request)
    {
        $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:500000'],
            'has_header' => ['nullable', 'boolean'],
            'delimiter' => ['nullable', 'string', 'max:5'],
        ]);

        $page = (int) $request->input('page', 1);
        $perPage = (int) $request->input('per_page', 50);
        $offset = ($page - 1) * $perPage;

        $hasHeader = (bool) $request->input('has_header', true);
        $delimiter = $request->input('delimiter', ',');

        // aici exact ce ai spus
        $path = $inventory->archive_path ?? $inventory->file_name;

        $disk = Storage::disk('epicor_inventory');

        if (!$disk->exists($path)) {
            return response()->json([
                'message' => 'File not found',
                'path' => $path
            ], 404);
        }

        $stream = $disk->readStream($path);

        $csv = Reader::createFromStream($stream);
        $csv->setDelimiter($delimiter);

        if ($hasHeader) {
            $csv->setHeaderOffset(0);
        }

        $stmt = (new Statement())
            ->offset($offset)
            ->limit($perPage);

        $records = $stmt->process($csv);

        return response()->json([
            'meta' => [
                'inventory_id' => $inventory->id,
                'page' => $page,
                'per_page' => $perPage,
                'offset' => $offset,
            ],
            'data' => iterator_to_array($records),
        ]);
    }
}