<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCandidateRequest extends FormRequest
{
    protected $languages = [];

    public function __construct()
    {
        $this->languages = array_keys(config('languages') ?? []);
    }

    public function authorize(): bool
    {
        return true; // Tùy chỉnh phân quyền nếu cần
    }

    public function prepareForValidation(): void
    {
        $jsonFields = [
            'full_name', 'education', 'experience_summary', 'gender',
            'industry_id', 'language', 'desired_location'
        ];

        foreach ($jsonFields as $field) {
            $value = $this->input($field);
            if (is_string($value)) {
                $this->merge([
                    $field => json_decode($value, true) ?? []
                ]);
            }
        }
    }

    public function rules(): array
    {
        $candidateId = $this->route('candidate')?->id ?? $this->input('id');

        $rules = [
            'phone' => [
                'required', 'string', 'max:20',
                Rule::unique('candidates', 'phone')->ignore($candidateId),
            ],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('candidates', 'email')->ignore($candidateId),
            ],
            'birthday' => ['required'],
            'location' => ['required'],
            'current_location' => ['required'],
            'desired_location' => ['required', 'array'],
            'language_other' => ['nullable', 'string', 'max:255'],
        ];

        $languages = array_keys(config('languages'));
        foreach ($languages as $lang) {
            $rules["full_name.$lang"] = 'required|string|max:255';
            $rules["gender.$lang"] = 'required|string|max:255';

            $rules["industry_id.$lang"] = 'required|array|min:1';
            $rules["industry_id.$lang.*.id"] = 'required|integer|exists:industries,id';
            $rules["industry_id.$lang.*.title"] = 'required|string|max:255';

            $rules["education.$lang"] = 'required|array';
            $rules["education.$lang.id"] = 'required|string|max:255';
            $rules["education.$lang.name"] = 'required|string|max:255';

            $rules["language.$lang"] = 'required|array';
            $rules["language.$lang.id"] = 'required|string|max:255';
            $rules["language.$lang.name"] = 'required|string|max:255';

            $rules["experience_summary.$lang"] = 'nullable|string';

            $rules["file_cv.$lang.cv_no_contact.file"] = 'nullable|file|mimes:pdf,doc,docx|max:10240';
            $rules["file_cv.$lang.cv_with_contact.file"] = 'nullable|file|mimes:pdf,doc,docx|max:10240';
        }
        return $rules;
    }

    public function messages(): array
    {
        return [
            'location.required' => 'Chỗ ở hiện tại là bắt buộc.',
            'birthday.required' => 'Ngày sinh là bắt buộc.',
            'full_name.*.required' => 'Tên đầy đủ là bắt buộc.',
            'phone.required' => 'Số điện thoại là bắt buộc.',
            'email.required' => 'Email là bắt buộc.',
            'gender.*.required' => 'Giới tính là bắt buộc.',
            
            'desired_location.required' => 'Khu vực mong muốn làm việc là bắt buộc.',
            'industry_id.*.required' => 'Ngành nghề là bắt buộc.',
            'industry_id.*.min' => 'Phải chọn ít nhất 1 ngành nghề.',
            'industry_id.*.*.id.required' => 'ID ngành nghề là bắt buộc.',
            'industry_id.*.*.id.exists' => 'ID ngành nghề không hợp lệ.',
            'industry_id.*.*.title.required' => 'Tên ngành nghề là bắt buộc.',
            'education.*.required' => 'Trình độ học vấn là bắt buộc.',
            'education.*.id.required' => 'ID học vấn là bắt buộc.',
            'education.*.name.required' => 'Tên học vấn là bắt buộc.',
            'language.*.required' => 'Ngôn ngữ là bắt buộc.',
            'language.*.id.required' => 'ID ngôn ngữ là bắt buộc.',
            'language.*.name.required' => 'Tên ngôn ngữ là bắt buộc.',
            'file_cv.*.cv_no_contact.file.mimes' => 'File CV không có thông tin liên hệ không đúng định dạng.',
            'file_cv.*.cv_no_contact.file.max' => 'File CV không có thông tin liên hệ không vượt quá 10MB.',
            'file_cv.*.cv_with_contact.file.mimes' => 'File CV có thông tin liên hệ không đúng định dạng.',
            'file_cv.*.cv_with_contact.file.max' => 'File CV có thông tin liên hệ không vượt quá 10MB.',
        ];
    }
}
