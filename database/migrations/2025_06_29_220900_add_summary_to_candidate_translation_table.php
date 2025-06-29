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
        Schema::table('candidate_translations', function (Blueprint $table) {
            $table->string('education')->after('full_name');
            $table->string('language')->after('education');
            $table->string('cv_no_contact')->after('experience_summary');
            $table->string('cv_with_contact')->after('cv_no_contact');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidate_translations', function (Blueprint $table) {
            $table->dropColumn('education');
            $table->dropColumn('language');
            $table->dropColumn('cv_no_contact');
            $table->dropColumn('cv_with_contact');
        });
    }
};
