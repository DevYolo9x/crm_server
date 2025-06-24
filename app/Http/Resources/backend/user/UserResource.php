<?php

namespace App\Http\Resources\backend\user;

use App\Enums\PackagingStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $role = $this->roles->first(); // Lấy role đầu tiên hoặc null

        return [
            'id' => $this->id,
            'code' => $this->code,
            'created_at' => date('Y-m-d H:i:s', strtotime($this->created_at)),
            'updated_at' => date('Y-m-d H:i:s', strtotime($this->updated_at)),
            'token' => $this->token,
            'name' => $this->name,
            'account' => $this->account,
            'phone' => $this->phone,
            'address' => $this->address,
            'gender' => $this->gender,
            'birthday' => $this->birthday,
            'email' => $this->email,
            'attachment_url' => (!empty($this->attachment) && file_exists(public_path($this->attachment)))
                ? asset($this->attachment)
                : '',
            'role' => $role ?: null, // Trả về null thay vì mảng rỗng
            'role_id' => $role->id ?? 0, // Trả về 0 nếu không có role
        ];
    }
}
