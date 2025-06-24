<?php

namespace App\Http\Controllers\backend\permission;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\PermissionRole;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PermissionController extends Controller
{
    protected $table = 'permissions';
    public function config()
    {
        return config('permissions');
    }
    public function index()
    {
        $data = Permission::latest()->where('parent_id', null)->orderBy('order', 'asc')->get();
        return $data;
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'permission_id' => 'required|array',
        ], [
            'title.required' => 'Tên module không được để trống.',
            'permission_id.required' => 'Quyền module không được để trống.',
            'permission_id.array' => 'Quyền module phải là một danh sách.',

        ]);
        $permission = Permission::create([
            'title' => $request->title,
            'publish' => 1
        ]);
        foreach ($request->permission_id as $v) {
            Permission::create([
                'title' => $v,
                'parent_id' => $permission->id,
                'key_code' => str_replace("-", "_", $permission->title) . '_' . $v,
                'publish' => 0
            ]);
        }
        return response()->json(['message' => 'Permission create successfully!']);
    }
    public function update(Request $request, $id)
    {
        $permission = Permission::with('children')->find($id);
        if (!empty($permission->children)) {
            PermissionRole::whereIn('permission_id', $permission->children->pluck('id'))->delete();
        }
        $permission->update([
            'publish' => $permission->publish == 1 ? 0 : 1
        ]);
        return response()->json(['message' => 'Permission update successfully!']);
    }
}
