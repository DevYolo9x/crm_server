<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;
    protected $fillable = [
        'customer_group_id',
        'code',
        'name',
        'tax_code',
        'address',
        'phone',
        'email',
        'attachment',
        'user_id',
    ];

    public function group()
    {
        return $this->belongsTo(CustomerGroup::class, 'customer_group_id', 'id');
    }
    public function jobs()
    {
        return $this->hasMany(Job::class);
    }
}
