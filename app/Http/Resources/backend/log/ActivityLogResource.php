<?php

namespace App\Http\Resources\backend\log;

use Illuminate\Http\Resources\Json\JsonResource;

class ActivityLogResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'model' => $this->model,
            'action' => $this->action,
            'user' => $this->user ? $this->user->name : '',
            'created_at' => date('Y-m-d H:i:s', strtotime($this->created_at)),
        ];
    }
}
