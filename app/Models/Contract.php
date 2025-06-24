<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'customer_id',
        'responsible_person_id',
        'created_at',
        'warranty_end_date',
        'invoice_date',
        'invoice_date_2',
        'total_amount',
        'first_payment',
        'second_payment',
        'notes',
        'payment_history_notes',
        'created_by',
        'deleted_at'
    ];
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function responsiblePerson()
    {
        return $this->belongsTo(User::class, 'responsible_person_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
    public function products()
    {
        return $this->hasMany(ContractProduct::class);
    }
}
