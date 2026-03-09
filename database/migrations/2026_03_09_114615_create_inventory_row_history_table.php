<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('inventory_row_history', function (Blueprint $table) {

            $table->id();

            $table->unsignedBigInteger('inventory_file_id')->index();
            $table->unsignedBigInteger('product_id')->nullable()->index();

            $table->string('sku')->index();

            $table->integer('csv_row');

            $table->string('action');


            $table->string('row_hash',64);

            $table->json('row_json');
            $table->json('changes_json')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_row_history');
    }
};