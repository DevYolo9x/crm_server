<?php

namespace App\Http\Resources\backend\candidateJob;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

class CandidateJobCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     *
     * @return array<int|string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'lists' => CandidateJobResource::collection($this->collection),
        ];
    }
}
