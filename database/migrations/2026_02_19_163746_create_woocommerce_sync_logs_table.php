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
        Schema::create('woocommerce_sync_logs', function (Blueprint $table) {
            $table->id();

            $table->string('sku')->index();
            $table->unsignedBigInteger('woocommerce_product_id')->nullable();

            $table->string('status')->index(); // updated, no_changes, not_found, error

            $table->json('old_data')->nullable();
            $table->json('new_data')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('woocommerce_sync_logs');
    }
};
