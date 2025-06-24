<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractProduct extends Model
{
    use HasFactory;
    protected $fillable = [
        'contract_id',
        'product',
        'price',
        'quantity',
        'discount',
        'tax',
        'total',
        'note',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }
}
