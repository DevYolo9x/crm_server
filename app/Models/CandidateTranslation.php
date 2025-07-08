<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CandidateTranslation extends Model
{
    use HasFactory;
    const UPDATED_AT = null;
    const CREATED_AT = null;
    protected $fillable = [
        'candidate_id',
        'alanguage',
        'full_name',
        'education',
        'gender',
        'language',
        'experience_summary',
        'cv_no_contact',
        'cv_with_contact',
        'updated_at',
        'created_at',
    ];
}
