<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;
    protected $fillable = ['user_id', 'action', 'model', 'model_id', 'changes'];
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }
}
