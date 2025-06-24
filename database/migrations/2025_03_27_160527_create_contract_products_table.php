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
        Schema::create('contract_products', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\Contract::class)->nullable();
            $table->string('product')->nullable();
            $table->decimal('price', 15, 0);
            $table->integer('quantity');
            $table->decimal('discount', 15, 0)->nullable();
            $table->decimal('tax', 15, 0)->nullable();
            $table->decimal('total', 15, 0);
            $table->text('note')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contract_products');
    }
};
