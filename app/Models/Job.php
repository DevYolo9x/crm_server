<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Job extends Model
{
    use HasFactory;
    protected $fillable = [
        'customer_id',
        'created_by',
        'job_title',
        'position',
        'company_info',
        'job_description',
        'requirements',
        'benefits',
        'additional_info',
        'jd_template',
        'status',
    ];
    public function customer()
    {
        return $this->hasOne(Customer::class, 'id', 'customer_id');
    }
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }
    public function candidates()
    {
        return $this->belongsToMany(Candidate::class, 'candidate_jobs', 'job_id', 'candidate_id');
    }
    public function locations()
    {
        return $this->hasMany(JobLocation::class);
    }
    public function users()
    {
        return $this->hasMany(JobUser::class);
    }
}
