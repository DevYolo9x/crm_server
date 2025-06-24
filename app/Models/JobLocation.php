<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JobLocation extends Model
{
    use HasFactory;
    protected $fillable = ['job_id', 'province_id'];

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function province()
    {
        return $this->belongsTo(VNCity::class, 'province_id', 'id');
    }
}
