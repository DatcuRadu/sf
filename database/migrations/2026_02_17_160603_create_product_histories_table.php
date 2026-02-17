<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_histories', function (Blueprint $table) {

            $table->id();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('field'); // ex: regular_price
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();

            $table->foreignId('changed_by')->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('changed_at');

            $table->timestamps();

            $table->index(['product_id', 'field']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_histories');
    }
};
