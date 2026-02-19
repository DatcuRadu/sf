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
        Schema::table('products', function (Blueprint $table) {

            $table->string('row_hash', 64)
                ->nullable()
                ->index();

            $table->boolean('to_sync')
                ->default(false)
                ->index();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['row_hash']);
            $table->dropIndex(['to_sync']);

            $table->dropColumn(['row_hash', 'to_sync']);
        });
    }
};
