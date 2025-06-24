<?php

namespace App\Http\Controllers\backend\auth;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\RoleUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function login(Request $request)
    {

        request()->validate([
            'account' => 'required',
            'password' => 'required',
        ], [
            'account.required' => 'Tên tài khoản là trường bắt buộc.',
            'password.required' => 'Mật khẩu là trường bắt buộc.',
        ]);
        $array = [
            'account' => $request->account,
            'password' => $request->password,
        ];
        if (!$token = Auth::guard('api')->attempt($array, true)) {
            return response()->json([
                'message' => [['Tên tài khoản hoặc mật khẩu không chính xác']]
            ], 422);
        }
        return $this->createNewToken($token);
    }
    protected function createNewToken($token)
    {
        $user =  auth()->user();
        // $user['permissions'] = $this->getPermissions();
        return response()->json([
            'token' => $token,
            'token_type' => 'bearer',
            'user' =>  $user,
            'status' => 200
        ], 200);
    }
    public function getPermissions()
    {
        $permissions = Permission::where('parent_id', null)->where('publish', 1)->orderBy('order', 'asc')->orderBy('id', 'asc')->get();
        $role_user = RoleUser::select('role_id')->where('user_id', Auth::user()->id)->first();
        $detailRole = Role::find($role_user->role_id);
        $permissionChecked = $detailRole->permissions;
        $resPermissions = [];
        foreach ($permissions as $k => $v) {
            $resPermissions[$v->title] = [];
            foreach ($v->children as $val) {
                if ($permissionChecked->contains('id', $val->id)) {
                    $resPermissions[$v->title][] = !empty($permissionChecked->contains('id', $val->id)) ? $val->title : "";
                }
            }
        }
        $resPermissions = collect($resPermissions)->reject(function ($value, $key) {
            return count($value) === 0;
        });
        return $resPermissions;
    }
    public function refresh()
    {
        return $this->respondWithToken(JWTAuth::refresh(JWTAuth::getToken()));
    }
    protected function respondWithToken($token)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60
        ]);
    }
    public function logout()
    {
        auth()->logout();
        return response()->json(['message' => 'Successfully logged out', 'status' => 200]);
    }
    public function profile()
    {
        $user = auth()->user();
        // $user['role_id'] = Auth::user()->role_users->role_id;
        // $user['permissions'] = $this->getPermissions();
        return response()->json([
            'user' => $user,
        ]);
    }

    public function permissions()
    {
        return $this->getPermissions();
    }
    public function update(Request $request)
    {
        $user = Auth::user();
        request()->validate([
            'name' => 'required',
            'email' => 'required|unique:users,email,' . $user->id,
            'phone' => 'required|unique:users,phone,' . $user->id,
            'password' => 'nullable|string|min:6|confirmed',

        ], [
            'name.required' => 'Họ và tên là trường bắt buộc. ',
            'email.required' => 'Email là trường bắt buộc. ',
            'email.unique' => 'Email đã tồn tại. ',
            'email.email' => 'Email không đúng định dạng. ',
            'phone.required' => 'Số điện thoại là trường bắt buộc. ',
            'phone.unique' => 'Số điện thoại đã tồn tại. ',
        ]);
        $data = $request->only([
            'name',
            'gender',
            'birthday',
            'phone',
            'email',
            'address',
        ]);
        if ($request->filled('password')) {
            $data['password'] = bcrypt(request('password'));
        }
        $user->update($data);
        return response()->json([
            'user' => $data,
            'message' => 'Cập nhập tài khoản thành công'
        ]);
    }
    public function changePassword(Request $request)
    {
        $user = Auth::user();
        $request->validate([
            'currentPassword' => 'required',
            'password' => 'required|min:3|max:20',
            'passwordConfirmation' => 'required|min:3|max:20|same:password',
        ], [
            'currentPassword.required' => 'Mật khẩu là trường bắt buộc.',
            'password.required' => 'Mật khẩu là trường bắt buộc.',
            'passwordConfirmation.required' => 'Nhập lại mật khẩu là trường bắt buộc.',
            'passwordConfirmation.same' => 'Nhập lại mật khẩu không khớp với mật khẩu.',
        ]);
        if (!Hash::check($request->currentPassword, $user->password)) {
            return response()->json([
                'errors' => ['currentPassword' => ['Mật khẩu cũ không đúng']]
            ], 422);
        }
        $request->user()->update(['password' => Hash::make($request->password)]);
        return response()->json(['message' => 'Thay đổi mật khẩu thành công!']);
    }
}
