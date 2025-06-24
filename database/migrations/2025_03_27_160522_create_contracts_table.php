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
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignIdFor(\App\Models\Customer::class)->nullable(); //khách hàng
            $table->foreignIdFor(\App\Models\User::class, 'responsible_person_id')->nullable(); // Hợp đồng đó thuộc của khách hàng nào
            $table->foreignIdFor(\App\Models\User::class, 'created_by')->nullable(); //người tạo
            $table->dateTime('warranty_end_date')->nullable(); //Ngày Kết thúc bảo hành hợp đồng
            $table->dateTime('invoice_date')->nullable(); //Ngày Kết thúc bảo hành hợp đồng
            $table->decimal('total_amount', 15, 0); //Số tiền hợp đồng
            $table->decimal('first_payment', 15, 0)->nullable(); //Số tiền hợp đồng 1
            $table->decimal('second_payment', 15, 0)->nullable(); //Số tiền hợp đồng 2
            $table->text('notes')->nullable(); //Ghi chú lịch sử chuyển khoản, job order. 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
