<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    use HasFactory;
    protected $fillable = ['publish', 'title', 'key_code', 'parent_id', 'order', 'created_at', 'updated_at'];
    public function children()
    {
        return $this->hasMany(Permission::class, 'parent_id');
    }
}
