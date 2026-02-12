<?php

namespace App\Http\Controllers;

use App\Services\Epicor\Inventory\InventoryProcessor;
use Illuminate\Http\JsonResponse;

class EpicorInventoryController extends Controller
{
    protected InventoryProcessor $processor;

    public function __construct(InventoryProcessor $processor)
    {
        $this->processor = $processor;
    }

    /**
     * Procesare DELTA
     */
    public function delta(): JsonResponse
    {
        $result = $this->processor->processDelta();

        return response()->json($result);
    }

    /**
     * Procesare FULL
     */
    public function full(): JsonResponse
    {
        $result = $this->processor->processFull();

        return response()->json($result);
    }
}
