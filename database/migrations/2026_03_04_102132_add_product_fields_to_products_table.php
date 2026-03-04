<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {

            $table->string('name')->nullable()->after('sku');

            $table->text('description')->nullable();

            $table->decimal('weight', 10, 2)->nullable();

            $table->decimal('length', 10, 2)->nullable();
            $table->decimal('width', 10, 2)->nullable();
            $table->decimal('height', 10, 2)->nullable();

        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {

            $table->dropColumn([
                'name',
                'description',
                'weight',
                'length',
                'width',
                'height'
            ]);

        });
    }
};
