<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Role extends Model
{
    use HasFactory;
    protected $fillable = ['title', 'user_id', 'created_at', 'updated_at'];
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'permission_roles', 'role_id', 'permission_id');
    }
    public function permission_roles()
    {
        return $this->hasMany(PermissionRole::class, 'role_id', 'id');
    }
}
