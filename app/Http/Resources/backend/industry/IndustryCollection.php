<?php

namespace App\Http\Resources\backend\industry;

use Illuminate\Http\Resources\Json\ResourceCollection;

class IndustryCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'lists' => IndustryResource::collection($this->collection),
            'pagination' => [
                'current_page' => $this->currentPage(),
                'last_page' => $this->lastPage(),
                'per_page' => $this->perPage(),
                'total' => $this->total(),
            ]
        ];
    }
}
