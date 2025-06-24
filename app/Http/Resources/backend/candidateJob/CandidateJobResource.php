<?php

namespace App\Http\Resources\backend\candidateJob;

use App\Enums\PackagingStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CandidateJobResource extends JsonResource
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
            'status' => $this->status,
            'candidate_id' => $this->candidate_id,
            'job_id' => $this->job_id,
            'user' => !empty($this->candidate->createBy) ? $this->candidate->createBy : [],
            'candidate' => !empty($this->candidate) ? $this->candidate : [],
            'created_at' => date('Y-m-d H:i:s', strtotime($this->created_at)),
            'updated_at' => date('Y-m-d H:i:s', strtotime($this->updated_at)),
        ];
    }
}
