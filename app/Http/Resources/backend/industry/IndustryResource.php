<?php

namespace App\Http\Resources\backend\industry;

use Illuminate\Http\Resources\Json\JsonResource;

class IndustryResource extends JsonResource
{
    public function toArray($request)
    {
        $titles['vi'] = $this->title;
        foreach ($this->industry_translations as $translation) {
            $titles[$translation->alanguage] = $translation->title;
        }
        return [
            'id' => $this->id,
            'title' => $this->getTranslatedField('title'),
            'createBy' => $this->createBy ? $this->createBy->name : '',
            'created_at' => date('Y-m-d H:i:s', strtotime($this->created_at)),
            'updated_at' => date('Y-m-d H:i:s', strtotime($this->updated_at)),
        ];
    }

    protected function getTranslatedField(string $field): array
    {  
        return $data = $this->industry_translations
        ->pluck($field, 'alanguage')
        ->filter()
        ->toArray();
    }
}
