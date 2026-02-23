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
        Schema::create('inventory_files', function (Blueprint $table) {
            $table->id();

            $table->string('file_name');
            $table->string('type'); // full | delta

            $table->integer('total_rows')->nullable();
            $table->integer('processed_rows')->default(0);

            $table->string('status')->default('pending');


            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();

            $table->text('error_message')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_files');
    }
};
