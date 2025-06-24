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
        Schema::create('candidates', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('phone', 20);
            $table->string('email')->unique();
            $table->foreignIdFor(\App\Models\Industry::class)->nullable();
            $table->string('education')->nullable();
            $table->string('language')->nullable();
            $table->string('language_other')->nullable();
            $table->string('current_location')->nullable();
            $table->string('desired_location')->nullable();
            $table->text('experience_summary')->nullable();
            $table->string('cv_no_contact')->nullable();
            $table->string('cv_with_contact')->nullable();
            $table->dateTime('expiry_date');
            $table->foreignIdFor(\App\Models\User::class, 'created_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidates');
    }
};
