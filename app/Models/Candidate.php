<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Candidate extends Model
{
    use HasFactory;
    protected $fillable = [
        'code',
        'full_name',
        'phone',
        'avatar',
        'birthday',
        'email',
        'industry_id',
        'education',
        'language',
        'language_other',
        'current_location',
        'experience_summary',
        'cv_no_contact',
        'cv_with_contact',
        'expiry_date',
        'created_by'
    ];
    public function industry()
    {
        return $this->belongsTo(Industry::class);
    }
    public function createBy()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }
    public function candidateJobs()
    {
        return $this->hasOne(CandidateJob::class, 'id', 'candidate_id');
    }
    public function desiredLocations()
    {
        return $this->hasMany(CandidateDesiredLocation::class, 'candidate_id');
    }
    public function users()
    {
        return $this->belongsToMany(User::class, 'candidate_users', 'candidate_id', 'user_id');
    }
    public function industries()
    {
        return $this->belongsToMany(Industry::class, 'candidate_industries', 'candidate_id', 'industry_id');
    }
    public function translations()
    {
        return $this->hasMany(CandidateTranslation::class);
    }
    public function translationByLang($lang = null)
    {
        $lang = $lang ?? app()->getLocale();

        return $this->hasOne(CandidateTranslation::class)->where('alanguage', $lang);
    }
    public function translation()
    {
        return $this->hasOne(CandidateTranslation::class);
    }
    
}
