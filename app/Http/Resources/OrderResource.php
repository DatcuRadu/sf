<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'order_number' => $this->order_number,
            'status' => $this->status,
            'epicor_status' => $this->epicor_status,
            'total' => $this->total,
            'currency' => $this->currency,

            'customer' => [
                'first_name' => $this->billing['first_name'] ?? null,
                'last_name'  => $this->billing['last_name'] ?? null,
                'email'      => $this->billing['email'] ?? null,
            ],

            'created_at' => $this->created_at,
        ];
    }
}
