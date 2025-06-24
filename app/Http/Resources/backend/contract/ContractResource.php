<?php

namespace App\Http\Resources\backend\contract;

use Illuminate\Http\Resources\Json\JsonResource;

class ContractResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'customer_id' => $this->customer_id,
            'customer' => $this->customer ? $this->customer->name : null,
            'user' => $this->user ? $this->user->name : null,
            'responsible_person_id' => $this->responsible_person_id,
            'responsible_person' => $this->responsiblePerson ? $this->responsiblePerson->name : null,
            'created_at' => date('Y-m-d H:i:s', strtotime($this->created_at)),
            'warranty_end_date' => date('Y-m-d', strtotime($this->warranty_end_date)),
            'invoice_date' => date('Y-m-d', strtotime($this->invoice_date)),
            'invoice_date_2' => date('Y-m-d', strtotime($this->invoice_date_2)),
            'total_amount' => $this->total_amount,
            'first_payment' => $this->first_payment,
            'second_payment' => $this->second_payment,
            'notes' => $this->notes,
            'payment_history_notes' => $this->payment_history_notes,
            'products' => $this->products->map(function ($product) {
                return [
                    'product' => $product->product,
                    'price' => $product->price,
                    'quantity' => $product->quantity,
                    'discount' => $product->discount,
                    'tax' => $product->tax,
                    'total' => $product->total,
                    'note' => $product->note,
                ];
            }),
        ];
    }
}
