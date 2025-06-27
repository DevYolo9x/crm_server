<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Industry extends Model
{
    use HasFactory;
    protected $fillable = ['title', 'created_by', 'parent_id', 'order', 'publish'];
    
    public function createBy()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function industry_translations()
    {
        return $this->hasMany(IndustryTranslation::class);
    }
}
