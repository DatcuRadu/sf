<?php

namespace App\Jobs;

use App\Models\Product;
use App\Services\Epicor\Inventory\InventoryProcessor;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessInventoryBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public string $file;
    public int $offset;
    public int $limit;

    public $timeout = 300;
    public $tries = 3;

    // 🔒 Coloane fixe Epicor
    private const COL_PRICE = 1;   // #2 Price
    private const COL_UPC   = 5;   // #5 UPC (SKU)
    private const COL_QTY   = 6;   // #7 Quantity on Hand
    private const COL_SALE  = 2;   // Sale Price
    private const GTIN  = 4;   // Sale Price

    public function __construct(string $file, int $offset, int $limit)
    {
        $this->file   = $file;
        $this->offset = $offset;
        $this->limit  = $limit;
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

            $qty   = (int)($row[self::COL_QTY] ?? 0);
            $price = (float)($row[self::COL_PRICE] ?? 0);
            $sale  = (float)($row[self::COL_SALE] ?? 0);
            $gtin = ($row[self::GTIN] ?? '');

            // 🔥 Hash doar pe ce contează
            $newHash = hash('sha256', json_encode([
                'qty'   => $qty,
                'price' => $price,
                'sale'  => $sale,
            ]));

            $product = Product::where('sku', $sku)
                ->select('id', 'row_hash')
                ->first();

            // ➕ Insert dacă lipsește
            if (!$product) {

                Product::create([
                    'sku'           => $sku,
                    'qty'           => $qty,
                    'regular_price' => $price,
                    'sale_price'    => $sale,
                    'row_hash'      => $newHash,
                    'to_sync'       => true,
                    'gitn'=>$gtin,
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
                    'gitn'=>$gtin,
                ]);
            }

            $processed++;
        }
    }
}
