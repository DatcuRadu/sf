<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'sku' => $this->sku,
            'name' => $this->name,
            'regular_price' => $this->regular_price,
            'sale_price' => $this->sale_price,
            'qty' => $this->qty,
            'deleted' => $this->deleted,
            'to_sync' => $this->to_sync,
            'woo_product_id' => $this->woo_product_id,
            'updated_at' => $this->updated_at,
        ];
    }
}
