<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\InventoryFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use League\Csv\Reader;
use League\Csv\Statement;

class InventoryFileController extends Controller
{
    public function index(Request $request)
    {
        $query = InventoryFile::query();

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $query->orderByDesc('received_at');

        $perPage = $request->input('per_page', 20);

        return $query->paginate($perPage);
    }

    public function show(InventoryFile $inventoryFile)
    {
        return $inventoryFile;
    }

    public function content($id)
    {
        ini_set('memory_limit', '-1'); // nelimitat
        $inventory = InventoryFile::findOrFail($id);

        $path = $inventory->archive_path ?: $inventory->file_name;

        $disk = Storage::disk('epicor_inventory');

        if (!$disk->exists($path)) {
            return response()->json([
                'error' => 'File not found',
                'path' => $path,
            ], 404);
        }

        $stream = $disk->readStream($path);

        if ($stream === false) {
            return response()->json([
                'error' => 'Unable to open file stream',
                'path' => $path,
            ], 500);
        }

        try {
            $csv = Reader::createFromStream($stream);
            $csv->setDelimiter(',');
            $csv->setHeaderOffset(null);

            $stmt = (new Statement())
                ->offset(0)
                ->limit(100000);

            $records = $stmt->process($csv);

            return response()->json([
                'file_name' => $inventory->file_name,
                'archive_path' => $inventory->archive_path,
                'path_used' => $path,
                'type' => $inventory->type,
                'status' => $inventory->status,
                'rows' => array_values(iterator_to_array($records, false)),
            ]);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }
}