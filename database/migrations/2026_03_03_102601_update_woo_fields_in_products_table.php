<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {

            $table->unsignedBigInteger('woo_product_id')
                ->nullable()
                ->after('original_id');

            $table->unsignedBigInteger('woo_parent_id')
                ->nullable()
                ->after('woo_product_id');

            $table->index('woo_product_id');
            $table->index('woo_parent_id');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {

            $table->dropIndex(['woo_product_id']);
            $table->dropIndex(['woo_parent_id']);

            $table->dropColumn([
                'woo_product_id',
                'woo_parent_id'
            ]);
        });
    }
};