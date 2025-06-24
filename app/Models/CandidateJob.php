<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CandidateJob extends Model
{
    use HasFactory;
    protected $fillable = [
        'candidate_id',
        'job_id',
        'customer_id',
        'status',
    ];
    // Mối quan hệ với Candidate
    public function candidate()
    {
        return $this->belongsTo(Candidate::class);
    }

    // Mối quan hệ với Job
    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    // Mối quan hệ với Customer
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
