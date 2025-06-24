<?php

namespace App\Http\Resources\backend\job;

use App\Enums\PackagingStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class JobResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        return [
            'id' => $this->id,
            'customer_id' => $this->customer_id,
            'customer' => $this->customer,
            'user' => $this->user,
            'created_by' => $this->created_by,
            'job_title' => $this->job_title,
            'position' => $this->position,
            'company_info' => $this->company_info,
            'job_description' => $this->job_description,
            'requirements' => $this->requirements,
            'benefits' => $this->benefits,
            'additional_info' => $this->additional_info,
            'jd_template' => $this->jd_template,
            'status' => $this->status,
            'created_at' => date('Y-m-d H:i:s', strtotime($this->created_at)),
            'updated_at' => date('Y-m-d H:i:s', strtotime($this->updated_at)),
            'locations' => $this->locations->map(function ($location) {
                return [
                    'id' => $location->province_id,
                    'name' => $location->province->name,
                ];
            }),
            'users' => $this->users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->user->code . ' - ' . $user->user->name,
                ];
            })->toArray(), // Thêm danh sách người phụ trách
        ];
    }
}
