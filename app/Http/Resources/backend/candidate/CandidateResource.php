<?php

namespace App\Http\Resources\backend\candidate;

use Illuminate\Http\Resources\Json\JsonResource;

class CandidateResource extends JsonResource
{
    public function toArray($request)
    {
        // return [
        //     'id' => $this->id,
        //     'code' => $this->code,
        //     'full_name' => [
        //         'vi' => $this->full_name
        //     ],
        //     'phone' => $this->phone,
        //     'email' => $this->email,
        //     'industry' => $this->industry ? $this->industry->title : '',
        //     'createBy' => $this->createBy ? $this->createBy->name : '',
        //     'education' => [
        //         'vi' => [
        //             'id' => $this->education,
        //             'name' => $this->education
        //         ]
        //     ],
        //     'language' => [
        //         'vi' => [
        //             'id' => $this->language,
        //             'name' => $this->language
        //         ]
        //     ],
        //     'language_other' => $this->language_other,
        //     'current_location' => $this->current_location,
        //     'desired_locations' => $this->desiredLocations->map(function ($location) {
        //         return [
        //             'location_id' => $location->location_id,
        //         ];
        //     }),
        //     'users' => $this->users->map(function ($user) {
        //         return [
        //             'id' => $user->id,
        //             'name' => $user->code . ' - ' . $user->name
        //         ];
        //     }),
        //     'industry_id' => $this->formatIndustriesByLocale(),
        //     'experience_summary' => [
        //         'vi' => $this->experience_summary
        //     ],
        //     'permission_update' => $this->permission_update,
        //     'file_cv' => [
        //         'vi' => [
        //             'cv_no_contact' => [
        //                 'url' => $this->cv_no_contact ? asset($this->cv_no_contact) : null,
        //                 'file' => new \stdClass()
        //             ],
        //             'cv_with_contact' => [
        //                 'url' => $this->cv_with_contact ? asset($this->cv_with_contact) : null,
        //                 'file' => new \stdClass()
        //             ]
                    
        //         ]
        //     ],
        //     'cv_no_contact' => $this->cv_no_contact ? asset($this->cv_no_contact) : null,
        //     'cv_with_contact' => $this->cv_with_contact ? asset($this->cv_with_contact) : null,
        //     'expiry_date' => date('Y-m-d H:i:s', strtotime($this->expiry_date)),
        //     'created_at' => date('Y-m-d H:i:s', strtotime($this->created_at)),
        //     'updated_at' => date('Y-m-d H:i:s', strtotime($this->updated_at)),
        // ];

        return [
            'id' => $this->id,
            'code' => $this->code,
            'full_name' => $this->getTranslatedField('full_name'),
            'phone' => $this->phone,
            'email' => $this->email,
            'industry' => $this->industry->title ?? '',
            'createBy' => $this->createBy->name ?? '',
            'education' => $this->getTranslatedFieldAsObject('education'),
            'language' => $this->getTranslatedFieldAsObject('language'),
            'language_other' => $this->language_other,
            'current_location' => $this->current_location,
            'desired_locations' => $this->desiredLocations->map(function ($location) {
                return ['location_id' => $location->location_id];
            }),
            'users' => $this->users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->code . ' - ' . $user->name
                ];
            }),
            'industry_id' => $this->formatIndustriesByLocale(),
            'experience_summary' => $this->getTranslatedField('experience_summary'),
            'permission_update' => $this->permission_update,
            'file_cv' => $this->getTranslatedFileCV(),
            'cv_no_contact' => $this->cv_no_contact ? asset($this->cv_no_contact) : null,
            'cv_with_contact' => $this->cv_with_contact ? asset($this->cv_with_contact) : null,
            'expiry_date' => $this->formatDate($this->expiry_date),
            'created_at' => $this->formatDate($this->created_at),
            'updated_at' => $this->formatDate($this->updated_at),
        ];
    }

    protected function getTranslatedField(string $field): array
    {
        return $this->translations
            ->pluck($field, 'alanguage')
            ->filter() // loại bỏ null
            ->toArray();
    }

    protected function getTranslatedFieldAsObject(string $field): array
    {
        return $this->translations
            ->pluck($field, 'alanguage')
            ->map(function ($value) {
                return ['id' => $value, 'name' => $value];
            })
            ->filter()
            ->toArray();
    }

    protected function getTranslatedFileCV(): array
    {
        return $this->translations
            ->pluck('file_cv', 'alanguage')
            ->map(function ($file) {
                return [
                    'cv_no_contact' => [
                        'url' => isset($file['cv_no_contact']) ? asset($file['cv_no_contact']) : null,
                        'file' => new \stdClass()
                    ],
                    'cv_with_contact' => [
                        'url' => isset($file['cv_with_contact']) ? asset($file['cv_with_contact']) : null,
                        'file' => new \stdClass()
                    ]
                ];
            })
            ->filter()
            ->toArray();
    }

    protected function formatDate($value)
    {
        return $value ? date('Y-m-d H:i:s', strtotime($value)) : null;
    }
    
    protected function formatIndustriesByLocale()
    {
        $result = [];
        foreach ($this->industries as $industry) {
            $result['vi'][] = [
                'id' => $industry->id,
                'title' => $industry->title,
            ];
            foreach ($industry->industry_translations as $translation) {
                $result[$translation->alanguage][] = [
                    'id' => $industry->id,
                    'title' => $translation->title,
                ];
            }
        }
        return $result;
    }
}
