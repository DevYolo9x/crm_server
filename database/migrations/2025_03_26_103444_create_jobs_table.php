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
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(\App\Models\Customer::class)->nullable();
            $table->foreignIdFor(\App\Models\User::class, 'created_by')->nullable();
            $table->string('job_title'); // Tên vị trí đăng tuyển
            $table->string('position'); // Vị trí tuyển dụng
            $table->text('company_info'); // Thông tin công ty
            $table->text('job_description'); // Mô tả công việc
            $table->text('requirements'); // Yêu cầu vị trí tuyển dụng
            $table->text('benefits'); // Chế độ phúc lợi
            $table->text('additional_info')->nullable(); // Các thông tin khác
            $table->string('jd_template')->nullable(); // File template JD
            $table->enum('status', ['Open', 'Pending', 'Urgent', 'Closed'])->default('Open'); // Tình trạng job
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobs');
    }
};
