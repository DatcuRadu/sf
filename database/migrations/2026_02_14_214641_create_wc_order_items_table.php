<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('wc_order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('wc_order_id')
                ->constrained('wc_orders')
                ->cascadeOnDelete();

            $table->string('sku')->index();
            $table->string('name');
            $table->integer('quantity');
            $table->decimal('price', 12, 4)->nullable();
            $table->decimal('total', 12, 2)->nullable();

            $table->json('raw_item'); // 🔥 păstrăm item complet

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wc_order_items');
    }
};
