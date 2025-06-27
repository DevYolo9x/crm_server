<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IndustryTranslation extends Model
{
    use HasFactory;

    protected $fillable = ['title', 'alanguage', 'industry_id'];

    public function industry()
    {
        return $this->belongsTo(Industry::class, 'industry_id');
    }

}
