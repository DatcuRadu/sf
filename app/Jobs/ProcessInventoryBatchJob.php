<?php

namespace App\Jobs;

use App\Models\Product;
use App\Services\Epicor\Inventory\InventoryProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Bus\Batchable;
use App\Models\InventoryFile;


class ProcessInventoryBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels,Batchable;

    public string $file;
    public int $offset;
    public int $limit;

    public $timeout = 300;
    public $tries = 3;

    // 🔒 Coloane fixe Epicor

    private const TITLE = 0;
    private const DESC = 3;
    private const COL_PRICE = 1;   // #2 Price
    private const COL_UPC   = 5;   // #5 UPC (SKU)
    private const COL_QTY   = 6;   // #7 Quantity on Hand
    private const COL_SALE  = 2;   // Sale Price
    private const GTIN  = 4;   // GTIN


    private const COL_SALE_START  = 60;
    private const COL_SALE_END  = 61;

    public $inventoryFileId;

    public function __construct(string $file, int $offset, int $limit, int $inventoryFileId)
    {
        $this->file   = $file;
        $this->offset = $offset;
        $this->limit  = $limit;
        $this->inventoryFileId = $inventoryFileId;
    }

    public function handle(InventoryProcessor $processor): void
    {
        $currentIndex = 0;
        $processed = 0;

        foreach ($processor->streamCsv($this->file) as $row) {

            //dd($row[5]);
            if ($currentIndex++ < $this->offset) {
                continue;
            }

            if ($processed >= $this->limit) {
                break;
            }

            // 🔑 SKU din UPC (index 4)
            $sku = trim($row[self::COL_UPC] ?? '');
            if (!$sku) {
                continue;
            }

            $title   = $row[self::TITLE] ?? '';

            $description   = $row[self::DESC] ?? '';

            $qty   = (int)($row[self::COL_QTY] ?? 0);
//            $price = (float)($row[self::COL_PRICE] ?? 0);
//            $sale  = (float)($row[self::COL_SALE] ?? 0);

            $price = $this->parseNumber($row[self::COL_PRICE] ?? 0);
            $sale  = $this->parseNumber($row[self::COL_SALE] ?? 0);
            $gtin = ($row[self::GTIN] ?? '');


            $sale_start = trim($row[self::COL_SALE_START] ?? '');
            $sale_end   = trim($row[self::COL_SALE_END] ?? '');

            $sale_start = $sale_start !== '' ? $sale_start : null;
            $sale_end   = $sale_end !== '' ? $sale_end : null;



            // 🔥 Hash doar pe ce contează
            $newHash = hash('sha256', json_encode([
                'qty'   => $qty,
                'price' => $price,
                'sale'  => $sale,
                'gtin'=>$gtin
            ]));

            $product = Product::where('sku', $sku)
                ->select('id', 'row_hash')
                ->first();

            // ➕ Insert dacă lipsește
            if (!$product) {

                Product::create([
                    'sku' => $sku,
                    'qty' => $qty,
                    'regular_price' => $price,
                    'sale_price' => $sale,
                    'row_hash' => $newHash,
                    'to_sync' => true,
                    'name' => $title,
                    'description' => $description,
                    'gitn' => $gtin,
                    'sales_start' => $sale_start,
                    'sales_end' => $sale_end,

                ]);

            }
            // 🔄 Update doar dacă diferă
            elseif ($product->row_hash !== $newHash) {

                Product::where('id', $product->id)->update([
                    'qty'           => $qty,
                    'regular_price' => $price,
                    'sale_price'    => $sale,
                    'row_hash'      => $newHash,
                    'to_sync'       => true,
                    'sales_start' => $sale_start,
                    'sales_end' => $sale_end,
                    'gitn'=>$gtin,
                ]);
            }

            $processed++;


        }

        InventoryFile::where('id', $this->inventoryFileId)
            ->increment('processed_rows', $processed);
    }

    private function parseNumber($value): float
    {
        return (float) str_replace(',', '', $value ?? 0);
    }
}
