<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {

            $table->id();

            // identificare
            $table->string('sku')->unique();
            $table->string('original_id')->nullable()->index();
            $table->string('gitn');

            // pricing
            $table->decimal('regular_price', 12, 2)->nullable();
            $table->decimal('sale_price', 12, 2)->nullable();

            // sales period
            $table->timestamp('sales_start')->nullable();
            $table->timestamp('sales_end')->nullable();

            // inventory
            $table->integer('qty')->default(0);

            // full raw payload
            $table->json('fields_json')->nullable();

            $table->timestamps();

            // Indexuri utile pentru performanță
            $table->index(['sku', 'gitn']);
            $table->index(['sales_start', 'sales_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
