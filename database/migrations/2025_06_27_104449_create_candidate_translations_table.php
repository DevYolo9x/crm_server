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
        Schema::create('candidate_translations', function (Blueprint $table) {
            $table->id();
            $table->string('alanguage');
            $table->foreignIdFor(\App\Models\Candidate::class)->nullable();
            $table->string('full_name')->nullable();
            $table->string('experience_summary')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidate_translations');
    }
};
