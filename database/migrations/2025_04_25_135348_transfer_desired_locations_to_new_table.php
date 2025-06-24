<?php

use App\Models\Candidate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $candidates = Candidate::all();
        foreach ($candidates as $candidate) {
            $desiredLocations = json_decode($candidate->desired_location, true);
            if (is_array($desiredLocations) && !empty($desiredLocations)) {
                foreach ($desiredLocations as $location) {
                    $locationId = is_array($location) ? $location['id'] : $location;
                    DB::table('candidate_desired_locations')->insert([
                        'candidate_id' => $candidate->id,
                        'location_id' => $locationId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }

        // Xóa cột desired_location sau khi chuyển dữ liệu
        Schema::table('candidates', function (Blueprint $table) {
            $table->dropColumn('desired_location');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('candidates', function (Blueprint $table) {
            $table->json('desired_location')->nullable();
        });
    }
};
