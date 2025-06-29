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
        $configs = Configuration::whereIn('key', ['candidate.education', 'candidate.language'])->get();
        $educations = [];
        $languages = [];
        if ($configs) {
            foreach ($configs as $config) {
                if ($config->key == 'candidate.education') {
                    $vi = $en = $kr = [];
                    foreach (preg_split('/\r\n|\r|\n/', $config->value) as $line) {
                        [$v, $e, $k] = array_map('trim', explode(' - ', $line));
                        $vi[] = $v;
                        $en[] = $e;
                        $kr[] = $k;
                    }
                    $vi = array_map(fn($item) => ['id' => $item, 'name' => $item], $vi);
                    $en = array_map(fn($item) => ['id' => $item, 'name' => $item], $en);
                    $kr = array_map(fn($item) => ['id' => $item, 'name' => $item], $kr);
                    $educations = compact('vi', 'en', 'kr');
                    //$educations = !empty($config->value) ? array_map(fn($value, $index) => ['id' => trim($value), 'name' => trim($value)], explode("\n", trim($config->value)), array_keys(explode("\n", trim($config->value)))) : [];
                } else if ($config->key == 'candidate.language') {
                    $vi = $en = $kr = [];
                    foreach (preg_split('/\r\n|\r|\n/', $config->value) as $line) {
                        [$v, $e, $k] = array_map('trim', explode(' - ', $line));
                        $vi[] = $v;
                        $en[] = $e;
                        $kr[] = $k;
                    }
                    $vi = array_map(fn($item) => ['id' => $item, 'name' => $item], $vi);
                    $en = array_map(fn($item) => ['id' => $item, 'name' => $item], $en);
                    $kr = array_map(fn($item) => ['id' => $item, 'name' => $item], $kr);
                    $languages = compact('vi', 'en', 'kr');
                    //$languages = !empty($config->value) ? array_map(fn($value, $index) => ['id' => trim($value), 'name' => trim($value)], explode("\n", trim($config->value)), array_keys(explode("\n", trim($config->value)))) : [];
                }
            }
        }
        return response()->json([
            'educations' => $educations,
            'languages' => $languages
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
                    ['key' => 'education', 'label' => 'Học vấn', 'type' => 'textarea'],
                    ['key' => 'language', 'label' => 'Ngoại ngữ', 'type' => 'textarea'],

                ],
            ],
        ];

        return response()->json($structure);
    }
}
