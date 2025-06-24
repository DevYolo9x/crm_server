<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CandidateDesiredLocation extends Model
{
    use HasFactory;
    protected $fillable = ['candidate_id', 'location_id'];

    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }
}
