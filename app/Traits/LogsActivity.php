<?php

namespace App\Traits;

use App\Models\ActivityLog;
use Illuminate\Support\Facades\Auth;

trait LogsActivity
{
    protected function logActivity($action, $model, $modelInstance, $message = '')
    {
        if (!Auth::check()) {
            return; // Tránh lỗi khi không có người dùng đăng nhập
        }
        $modelName = class_basename($model);
        $displayName = $modelInstance->name ?? $modelInstance->title ?? $modelInstance->full_name ?? $modelInstance->id;
        $description = !empty($message) ? $message : $this->generateDescription($action, $modelName, $displayName);
        $changes = null;
        if ($action === 'update' && $modelInstance->exists) {
            $changes = [
                'old' => $modelInstance->getOriginal(),
                'new' => $modelInstance->getChanges(),
            ];
        }
        ActivityLog::create([
            'user_id' =>  Auth::user()->id,
            'action' => $description,
            'model' => $modelName,
            'model_id' => $modelInstance->id ?? null,
            'changes' => $changes ? json_encode($changes) : null
        ]);
    }

    private function generateDescription($action, $modelName, $displayName)
    {
        $modelVietnamese = $this->getModelName($modelName);
        switch ($action) {
            case 'create':
                return "Đã thêm mới {$modelVietnamese} {$displayName}";
            case 'update':
                return "Đã sửa thông tin {$modelVietnamese} {$displayName}";
            case 'delete':
                return "Đã xóa {$modelVietnamese} {$displayName}";
            case 'delete_attachment':
                return "Đã xóa file";
            default:
                return "Thực hiện hành động trên {$modelVietnamese} {$displayName}";
        }
    }

    private function getModelName($model)
    {
        return match ($model) {
            'CustomerGroup' => 'nhóm khách hàng',
            'Customer' => 'khách hàng',
            'Job' => 'công việc',
            'Industry' => 'nhóm ngành nghề',
            'Candidate' => 'ứng viên',
            'Contract' => 'hợp đồng',
            'User' => 'nhân viên',
            'CandidateJob' => 'gán ứng viên',
            default => strtolower($model),
        };
    }
}
