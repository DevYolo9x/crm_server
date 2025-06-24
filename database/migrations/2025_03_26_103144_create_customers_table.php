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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\CustomerGroup::class)->nullable();
            $table->string('code')->unique(); // code
            $table->string('type'); // 'company' hoặc 'individual'
            $table->string('name'); // Tên công ty hoặc cá nhân
            $table->string('tax_code')->unique(); // Mã số thuế
            $table->string('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->string('attachment')->nullable(); // File đính kèm
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
