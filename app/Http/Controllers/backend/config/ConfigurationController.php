<?php

namespace App\Http\Controllers\backend\config;

use App\Http\Controllers\Controller;
use App\Models\Configuration;
use App\Models\VNCity;
use Illuminate\Http\Request;

class ConfigurationController extends Controller
{
    public function cities()
    {
        $cities = VNCity::all();
        $groupedCities = [];
        foreach ($cities as $city) {
            $groupedCities[] = [
                'id' => $city->id,
                'name' => $city->name,
            ];
        }
        return response()->json($groupedCities);
    }
    public function candidate()
    {
        $configs = Configuration::whereIn('key', ['candidate.education', 'candidate.education_en', 'candidate.education_ja', 'candidate.language', 'candidate.language_en', 'candidate.language_ja'])->get();
        $educations = [];
        $languages = [];
        $result = [
            'education' => [],
            'language' => [],
        ];
        foreach ($configs as $item) {
            // Xác định loại
            if (str_starts_with($item->key, 'candidate.education')) {
                $type = 'education';
            } elseif (str_starts_with($item->key, 'candidate.language')) {
                $type = 'language';
            } else {
                continue;
            }
        
            // Xác định ngôn ngữ
            $lang = 'vi';
            if (preg_match('/_(\w+)$/', $item->key, $matches)) {
                $lang = $matches[1];
            }
        
            // Tách từng dòng
            $lines = preg_split('/\r\n|\n|\r/', $item->value);
        
            // Nếu là 'vi' và có cấu trúc "Đại học - English - Japanese"
            if ($lang === 'vi') {
                $lines = array_map(function ($line) {
                    return explode(' - ', $line)[0];
                }, $lines);
            }
        
            // Biến thành mảng ['id' => ..., 'name' => ...]
            $items = array_map(function ($val) {
                return ['id' => trim($val), 'name' => trim($val)];
            }, $lines);
        
            $result[$type][$lang] = $items;
        }
        
        return response()->json([
            'educations' => $result['education'],
            'languages' => $result['language']
        ]);
    }

    public function getLanguages()
    {
        $data = collect(config('languages'))->map(function ($name, $code) {
            return [
                'code' => $code,
                'name' => $name,
            ];
        })->values();
        return response()->json($data);
    }

    public function index()
    {
        $configs = Configuration::all();
        $groupedConfigs = [];
        foreach ($configs as $config) {
            [$group, $key] = explode('.', $config->key, 2);
            if (!isset($groupedConfigs[$group])) {
                $groupedConfigs[$group] = [];
            }
            $groupedConfigs[$group][$key] = $config->value;
        }
        return response()->json($groupedConfigs);
    }
    public function update(Request $request)
    {
        $data = $request->all();

        foreach ($data as $group => $groupData) {
            foreach ($groupData as $key => $value) {
                $fullKey = "$group.$key";
                Configuration::updateOrCreate(
                    ['key' => $fullKey],
                    ['value' => $value]
                );
            }
        }
        return response()->json(['message' => 'Cập nhập cấu hình thành công']);
    }
    public function uploadFile(Request $request)
    {
        $request->validate([
            'file' => 'required|image|max:2048',
            'group' => 'required',
            'key' => 'required'
        ]);
        $fullKey = $request->group . '.' . $request->key;
        $file = $request->file('file');
        $fileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        // Define the path to save the file
        $destinationPath = public_path('uploads/images/config');
        // Ensure the upload directory exists
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0777, true);
        }
        // Move the file to the specified path
        $file->move($destinationPath, $fileName);
        // Update the user's profile picture path in the database
        $url = 'uploads/images/config/' . $fileName;
        Configuration::updateOrCreate(
            ['key' => $fullKey],
            ['value' => asset($url)]
        );
        return response()->json(['url' => asset($url), 'message' => 'Cập nhập cấu hình thành công']);
    }
    public function getConfigStructure()
    {
        $structure = [
            'tabs' => [
                ['key' => 'general', 'label' => 'Thông tin chung'],
                ['key' => 'contact', 'label' => 'Thông tin liên lạc'],
                ['key' => 'seo', 'label' => 'Cấu hình tiêu đề'],
                ['key' => 'candidate', 'label' => 'Ứng viên'],
            ],
            'fieldConfig' => [
                'general' => [
                    ['key' => 'company', 'label' => 'Tên công ty, tổ chức, cá nhân', 'type' => 'text'],
                    ['key' => 'logo', 'label' => 'Logo', 'type' => 'image'],
                    ['key' => 'favicon', 'label' => 'Favicon', 'type' => 'image'],
                ],
                'contact' => [
                    ['key' => 'address', 'label' => 'Địa chỉ', 'type' => 'text'],
                    ['key' => 'phone', 'label' => 'Số điện thoại', 'type' => 'text'],
                    ['key' => 'hotline', 'label' => 'Hotline', 'type' => 'text'],
                    ['key' => 'email', 'label' => 'Email', 'type' => 'text'],
                ],
                'seo' => [
                    ['key' => 'meta_title', 'label' => 'Tiêu đề SEO', 'type' => 'text'],
                    ['key' => 'meta_description', 'label' => 'Mô tả SEO', 'type' => 'textarea'],
                    ['key' => 'meta_keyword', 'label' => 'Keyword SEO', 'type' => 'textarea'],
                ],
                'candidate' => [
                    ['key' => 'expiration_date', 'label' => 'Ngày hết hạn(ngày)', 'type' => 'text'],
                    ['key' => 'education', 'label' => 'Học vấn (VI)', 'type' => 'textarea'],
                    ['key' => 'education_en', 'label' => 'Học vấn (EN)', 'type' => 'textarea'],
                    ['key' => 'education_ja', 'label' => 'Học vấn (JP)', 'type' => 'textarea'],
                    ['key' => 'language', 'label' => 'Ngoại ngữ (VI)', 'type' => 'textarea'],
                    ['key' => 'language_en', 'label' => 'Ngoại ngữ (EN)', 'type' => 'textarea'],
                    ['key' => 'language_ja', 'label' => 'Ngoại ngữ (JP)', 'type' => 'textarea'],

                ],
            ],
        ];

        return response()->json($structure);
    }
}
