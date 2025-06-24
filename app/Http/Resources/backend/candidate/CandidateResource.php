<?php

namespace App\Http\Resources\backend\candidate;

use Illuminate\Http\Resources\Json\JsonResource;

class CandidateResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'full_name' => $this->full_name,
            'phone' => $this->phone,
            'email' => $this->email,
            'industry' => $this->industry ? $this->industry->title : '',
            'createBy' => $this->createBy ? $this->createBy->name : '',
            'education' => $this->education,
            'language' => $this->language,
            'language_other' => $this->language_other,
            'current_location' => $this->current_location,
            'desired_locations' => $this->desiredLocations->map(function ($location) {
                return [
                    'location_id' => $location->location_id,
                ];
            }),
            'experience_summary' => $this->experience_summary,
            'industry_id' => $this->industry_id,
            'cv_no_contact' => $this->cv_no_contact ? asset($this->cv_no_contact) : null,
            'cv_with_contact' => $this->cv_with_contact ? asset($this->cv_with_contact) : null,
            'expiry_date' => date('Y-m-d H:i:s', strtotime($this->expiry_date)),
            'created_at' => date('Y-m-d H:i:s', strtotime($this->created_at)),
            'updated_at' => date('Y-m-d H:i:s', strtotime($this->updated_at)),
        ];
    }
}
