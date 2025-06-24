<?php

namespace App\Http\Resources\backend\customer;

use App\Enums\PackagingStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CustomerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $attachments = json_decode($this->attachment, true) ?? [];
        $fullPaths = array_map(function ($path) {
            return [
                'url' => url($path), // Đường dẫn đầy đủ
                'url_origin' => $path, // Đường dẫn đầy đủ
                'name' => basename($path) // Tên file gốc
            ];
        }, $attachments);
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'group' => $this->group,
            'tax_code' => $this->tax_code,
            'address' => $this->address,
            'attachment' => $fullPaths,
            'code' => $this->code,
            'created_at' => date('Y-m-d H:i:s', strtotime($this->created_at)),
            'updated_at' => date('Y-m-d H:i:s', strtotime($this->updated_at)),
        ];
    }
}
