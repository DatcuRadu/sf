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
        Schema::create('wc_orders', function (Blueprint $table) {
            $table->id();

            $table->bigInteger('wc_order_id')->unique();
            $table->string('order_number')->nullable();
            $table->string('status')->index();
            $table->decimal('total', 12, 2)->nullable();
            $table->string('currency', 10)->nullable();

            $table->json('billing')->nullable();
            $table->json('shipping')->nullable();

            $table->json('raw_payload'); // 🔥 păstrăm TOT json

            $table->enum('epicor_status', [
                'pending',
                'processing',
                'sent',
                'failed'
            ])->default('pending');

            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wc_orders');
    }
};
