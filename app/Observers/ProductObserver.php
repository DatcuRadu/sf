<?php

namespace App\Observers;

use App\Models\Product;
use App\Models\ProductHistory;
use Illuminate\Support\Facades\Auth;

class ProductObserver
{
    public function updating(Product $product)
    {
        $dirty = $product->getDirty();

        foreach ($dirty as $field => $newValue) {

            $oldValue = $product->getOriginal($field);

            // Salvăm doar dacă chiar s-a schimbat
            if ($oldValue != $newValue) {

                ProductHistory::create([
                    'product_id' => $product->id,
                    'field'      => $field,
                    'old_value'  => $oldValue,
                    'new_value'  => $newValue,
                    'changed_by' => Auth::id(),
                    'changed_at' => now(),
                ]);
            }
        }
    }
}
