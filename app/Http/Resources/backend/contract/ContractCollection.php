<?php

namespace App\Http\Resources\backend\contract;

use Illuminate\Http\Resources\Json\ResourceCollection;

class ContractCollection extends ResourceCollection
{
    public function toArray($request)
    {
        return [
            'lists' => ContractResource::collection($this->collection),
            'pagination' => [
                'current_page' => $this->currentPage(),
                'last_page' => $this->lastPage(),
                'per_page' => $this->perPage(),
                'total' => $this->total(),
            ]
        ];
    }
}
