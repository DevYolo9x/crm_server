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
        Schema::create('candidate_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\Customer::class)->nullable(); //khách hàng
            $table->foreignIdFor(\App\Models\Job::class)->nullable(); //job order
            $table->foreignIdFor(\App\Models\Candidate::class)->nullable(); //ứng viên
            $table->foreignIdFor(\App\Models\User::class, 'created_by')->nullable(); //người tạo
            $table->string('status')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('candidate_jobs');
    }
};
