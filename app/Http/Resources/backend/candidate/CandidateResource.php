<?php

namespace App\Http\Resources\backend\candidate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Resources\Json\JsonResource;

class CandidateResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'avatar' => new \stdClass(),
            'birthday' => !empty($this->birthday) ? date('Y-m-d', strtotime($this->birthday)) : '',
            'gender' => $this->getTranslatedField('gender'),
            'avatar_url' => !empty($this->avatar) ? asset($this->avatar) : asset('uploads/company/avatar-default.jpg'),
            'full_name' => $this->getTranslatedField('full_name'),
            'phone' => $this->checkPermission() ? $this->maskPhone($this->phone) : $this->phone,
            'email' => $this->checkPermission() ? $this->maskPhone($this->email) : $this->email,
            'industry' => $this->industry->title ?? '',
            'createBy' => $this->createBy->name ?? '',
            'education' => $this->getTranslatedFieldAsObject('education'),
            'language' => $this->getTranslatedFieldAsObject('language'),
            'language_other' => $this->language_other,
            'current_location' => (int)$this->current_location,
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
            'skills' => $this->getDecodedFieldJson('skills') != null ? $this->getDecodedFieldJson('skills') : new \stdClass(),
            'work_experience' => ($this->getDecodedFieldJson('work_experience') != null) ? $this->getDecodedFieldJson('work_experience') : new \stdClass(),
            'time_education' => $this->getDecodedFieldJson('time_education') != null ? $this->getDecodedFieldJson('time_education') : new \stdClass(),
            'permission_update' => $this->checkPermission() ? false : true,
            'file_cv' => $this->getTranslatedFileCV(),
            'strength' => $this->getTranslatedField('strength'),
            'cv_no_contact' => $this->cv_no_contact ? asset($this->cv_no_contact) : null,
            'cv_with_contact' => $this->cv_with_contact ? asset($this->cv_with_contact) : null,
            'expiry_date' => $this->formatDate($this->expiry_date),
            'created_at' => $this->formatDate($this->created_at),
            'updated_at' => $this->formatDate($this->updated_at),
        ];
    }

    protected function checkPermission() : bool 
    {
        $user = Auth::user();
        $hasAccess = $this->users->contains('id', $user->id);
        $canViewAll = $user->can('candidates_all');
        $isAdmin = $user->can('candidates_administrator');
        $isCreator = $this->created_by === $user->id;
        if ($canViewAll && !$isAdmin && !$isCreator && !$hasAccess){
            return true;
        }
        return false;
    }

    protected function getTranslatedField(string $field): object
    {
        $data = $this->translations
        ->pluck($field, 'alanguage')
        ->filter()
        ->toArray();
        return (object) $data;
    }

    protected function getDecodedFieldJson(string $field, string $languageKey = 'alanguage')
    {
        return $this->translations
            ->filter(function ($item) use ($field) {
                return !empty($item->{$field});
            })
            ->mapWithKeys(function ($item) use ($field, $languageKey) {
                return [
                    $item->{$languageKey} => json_decode($item->{$field}, true),
                ];
            })
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
        ->keyBy('alanguage') // dùng 'alanguage' làm key chính
        ->map(function ($item) {
            return [
                'cv_no_contact' => [
                    'url' => $this->checkPermission() ? null : ($item->cv_no_contact ? asset($item->cv_no_contact) : null),
                    'file' => new \stdClass()
                ],
                'cv_with_contact' => [
                    'url' => $this->checkPermission() ? null : ($item->cv_with_contact ? asset($item->cv_with_contact) : null),
                    'file' => new \stdClass()
                ]
            ];
        })
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
            foreach ($industry->industry_translations as $translation) {
                $result[$translation->alanguage][] = [
                    'id' => $industry->id,
                    'title' => $translation->title,
                ];
            }
        }
        return $result;
    }

    protected function maskPhone($phone)
    {
        if (strlen($phone) < 4) return str_repeat('x', strlen($phone));
        return substr($phone, 0, 2) . str_repeat('x', strlen($phone) - 4) . substr($phone, -2);
    }

    protected function maskEmail($email)
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) return str_repeat('*', strlen($email));
    
        $name = $parts[0];
        $domain = $parts[1];
    
        $visible = max(1, floor(strlen($name) / 3));
        return substr($name, 0, $visible) . str_repeat('*', strlen($name) - $visible) . '@' . $domain;
    }
}
