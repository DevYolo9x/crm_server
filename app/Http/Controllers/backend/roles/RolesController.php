<?php

namespace App\Http\Controllers\backend\roles;

use App\Http\Controllers\Controller;
use App\Http\Resources\backend\roles\RolesCollection;
use App\Http\Resources\backend\roles\RolesResource;
use App\Models\Permission;
use App\Models\PermissionRole;
use App\Models\Role;
use App\Models\RoleUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\App;

class RolesController extends Controller
{
    protected $table = 'roles';
    public function permission()
    {
        $query = Permission::where('parent_id', null)
            ->with('children')
            ->where('publish', 1)
            ->orderBy('order')
            ->orderBy('id');
        return $query->get();
    }
    public function getRoles(Request $request)
    {
        $data = Role::query()
            ->when(App::environment('production'), function ($query) {
                return $query->where('id', '!=', 1);
            })
            ->latest()
            ->get();
        return $data;
    }

    public function index(Request $request)

    {
        $keyword = $request->input('keyword');
        $perPage = $request->input('per_page', 20); // Default 10 items per page
        $page = $request->input('page', 1); // Default page 1
        $data = Role::query()->when(App::environment('production'), function ($query) {
            return $query->where('id', '!=', 1);
        });
        $data = $data->when($keyword, function ($query, $keyword) {
            $query->where('title', 'like', "%{$keyword}%");
        });
        $data = $data->latest()->paginate($perPage, ['*'], 'page', $page);
        return response()->json(new RolesCollection($data));
    }

    public function store(Request $request)
    {
        request()->validate([
            'title' => 'required',
            'permission_id' => 'required|array',
        ], [
            'title.required' => 'Tên nhóm thành viên là trường bắt buộc.',
        ]);
        $role = Role::create([
            'title' => $request->title,
            'user_id' => Auth::user()->id,
        ]);
        if (!empty($role)) {
            $role->permissions()->attach($request->permission_id);
        }
        return response()->json(['message' => 'Thêm mới nhóm thành viên thành công!', 'role' => new RolesResource($role)]);
    }

    public function update(Request $request, $id)

    {
        request()->validate([
            'title' => 'required',
            'permission_id' => 'required|array',
        ], [
            'title.required' => 'Tên nhóm thành viên là trường bắt buộc.',
        ]);
        $roleUpdate =  Role::where(['id' => $id]);
        $roleUpdate = $roleUpdate->update([
            'title' => $request->title,
        ]);
        $role = Role::with('permission_roles')->where(['id' => $id]);
        $role =  $role->first();
        if (!empty($role)) {
            $role->permissions()->sync($request->permission_id);
        }

        return response()->json(['message' => 'Cập nhập nhóm thành viên thành công!', 'role' => new RolesResource($role)]);
    }

    public function show($id)

    {
        $role = Role::with('permission_roles')->where(['id' => $id]);
        $role =  $role->first();
        return response()->json(['role' => $role, 'permission_id' => $role->permission_roles->pluck('permission_id')]);
    }

    public function destroy($id)
    {
        PermissionRole::where('role_id', $id)->delete();
        Role::find($id)->delete();
        RoleUser::select('role_id')->where('role_id', $id)->delete();
        return response()->json([
            'message' => "Xóa nhóm thành viên thành công",
        ]);
    }
}
