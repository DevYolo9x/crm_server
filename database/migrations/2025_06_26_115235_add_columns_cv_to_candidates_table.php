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
        Schema::table('candidates', function (Blueprint $table) {
            $table->string('cv_no_contact_en')->nullable()->after('cv_with_contact');
            $table->string('cv_with_contact_en')->nullable()->after('cv_no_contact_en');
            $table->string('cv_no_contact_cn')->nullable()->after('cv_with_contact_en');
            $table->string('cv_with_contact_cn')->nullable()->after('cv_no_contact_cn');
            $table->string('cv_no_contact_kr')->nullable()->after('cv_with_contact_cn');
            $table->string('cv_with_contact_kr')->nullable()->after('cv_no_contact_kr');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn(['cv_no_contact_en', 'cv_with_contact_en', 'cv_no_contact_cn', 'cv_with_contact_cn', 'cv_no_contact_kr', 'cv_with_contact_kr']);
        });
    }
};
