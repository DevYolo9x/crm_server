<?php

namespace App\Http\Controllers\backend\user;

use App\Http\Controllers\Controller;
use App\Http\Resources\backend\user\UserCollection;
use App\Http\Resources\backend\user\UserResource;
use App\Models\RoleUser;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use App\Traits\LogsActivity;
use Illuminate\Support\Facades\App;

class UserController extends Controller
{
    use LogsActivity;
    public function __construct() {}
    public function lists(Request $request)
    {
        $keyword = $request->input('keyword');
        if (auth()->user()->can('users_index')) {
            return User::select('id', 'name', 'code')
                ->orderBy('name', 'asc')
                ->get()
                ->map(fn($user) => [
                    'id' => $user->id,
                    'name' => $user->code . ' - ' . $user->name
                ]);
        } else {
            return User::select('id', 'name', 'code')
                ->where('id', auth()->user()->id)
                ->when($keyword, function ($query) use ($keyword) {
                    $query->where('name', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%")
                        ->orWhere('id', 'like', "%{$keyword}%")
                        ->orWhere('code', 'like', "%{$keyword}%")
                        ->orWhere('account', 'like', "%{$keyword}%")
                        ->orWhere('phone', 'like', "%{$keyword}%")
                    ;
                })
                ->orderBy('name', 'asc')
                ->get()
                ->map(fn($user) => [
                    'id' => $user->id,
                    'name' => $user->code . ' - ' . $user->name
                ]);
        }

        return [];
    }

    public function search(Request $request) 
    {
        $keyword = $request->input('keyword');
        return User::select('id', 'name', 'code')
            ->where('id', auth()->user()->id)
            ->when($keyword, function ($query) use ($keyword) {
                $query->where('name', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%")
                    ->orWhere('id', 'like', "%{$keyword}%")
                    ->orWhere('code', 'like', "%{$keyword}%")
                    ->orWhere('account', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%")
                ;
            })
            ->orderBy('name', 'asc')
            ->get()
            ->map(fn($user) => [
                'id' => $user->id,
                'name' => $user->code . ' - ' . $user->name
            ]);
    }
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);
        $keyword = $request->input('keyword');
        $roleId = $request->input('role_id'); // Lọc theo role_id
        $users = User::with(['role_users:id,user_id,role_id', 'roles'])
            ->when(App::environment('production'), function ($query) {
                return $query->where('id', '!=', 1);
            })
            ->when($keyword, function ($query) use ($keyword) {
                $query->where(function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%")
                        ->orWhere('id', 'like', "%{$keyword}%")
                        ->orWhere('code', 'like', "%{$keyword}%")
                        ->orWhere('account', 'like', "%{$keyword}%")
                        ->orWhere('phone', 'like', "%{$keyword}%");
                });
            })
            ->when($roleId, function ($query) use ($roleId) {
                $query->whereHas('role_users', function ($q) use ($roleId) {
                    $q->where('role_id', $roleId);
                });
            })
            ->latest()
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json(new UserCollection($users));
    }
    public function store(Request $request)
    {
        request()->validate([
            'account' => 'required|unique:users,account',
            'name' => 'required',
            'email' => 'required|unique:users,email',
            'phone' => 'required|unique:users,phone',
            'password' => 'required|min:8',
        ], [
            'account.required' => 'Tên đăng nhập là trường bắt buộc. ',
            'account.unique' => 'Tên đăng nhập đã tồn tại. ',
            'name.required' => 'Tên nhân viên là trường bắt buộc. ',
            'email.required' => 'Email là trường bắt buộc. ',
            'email.unique' => 'Email đã tồn tại. ',
            'email.email' => 'Email không đúng định dạng. ',
            'phone.required' => 'Số điện thoại là trường bắt buộc. ',
            'phone.unique' => 'Số điện thoại đã tồn tại. ',
            'password.required' => 'Mật khẩu là trường bắt buộc. ',
            'password.min' => 'Mật khẩu tối thiểu là 8 kí tự. ',
        ]);
        $fileUrl = '';
        if ($request->hasFile("attachment")) {
            $file = $request->file("attachment");
            // Kiểm tra file hợp lệ
            if ($file->isValid()) {
                // Lấy phần mở rộng của file
                $extension = $file->getClientOriginalExtension();
                // Chỉ cho phép các file PDF, Excel
                if (!in_array($extension, ['pdf', 'xls', 'xlsx'])) {
                    return response()->json(['error' => 'Định dạng file không hợp lệ!'], 422);
                }
                // Tạo tên file duy nhất
                $fileName = time() . '_' . uniqid() . '.' . $extension;

                // Đường dẫn thư mục lưu trữ trong `public/uploads/users/`
                $folderPath = 'uploads/users/' . date('Y') . '/' . date('m') . '/' . date('d');
                $destinationPath = public_path($folderPath);

                // Kiểm tra và tạo thư mục nếu chưa tồn tại
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                // Di chuyển file vào thư mục public
                $file->move($destinationPath, $fileName);
                // Đường dẫn truy cập file từ trình duyệt
                $fileUrl = "$folderPath/$fileName";
            }
        }
        $lastUser = User::orderBy('id', 'desc')->first();
        if ($lastUser) {
            $lastCode = (int) filter_var($lastUser->code, FILTER_SANITIZE_NUMBER_INT); // Lấy số từ mã KHxxx
            $newCode = 'NV' . str_pad($lastCode + 1, 3, '0', STR_PAD_LEFT); // Tăng lên 1 và định dạng 3 chữ số
        } else {
            $newCode = 'NV001'; // Nếu chưa có khách hàng nào, bắt đầu từ KH001
        }
        $user = User::create([
            'code' => $newCode,
            'account' => request('account'),
            'name' => request('name'),
            'address' => request('address'),
            'gender' => request('gender'),
            'birthday' => request('birthday'),
            'phone' => request('phone'),
            'email' => request('email'),
            'password' => bcrypt(request('password')),
            'token' => Str::random(10),
            'attachment' => $fileUrl
        ]);
        $user->roles()->attach($request->role_id);
        $this->logActivity('create', User::class, $user);
        return response()->json(['message' => 'Thêm mới nhân viên thành công!', 'user' => new UserResource($user)]);
    }
    public function update(Request $request, $id)
    {
        $user = User::where(['id' => $id])->first();
        request()->validate([
            'account' => 'required|unique:users,account,' . $user->id,
            'name' => 'required',
            'email' => 'required|unique:users,email,' . $user->id,
            'phone' => 'required|unique:users,phone,' . $user->id,
        ], [
            'account.required' => 'Tên đăng nhập là trường bắt buộc. ',
            'account.unique' => 'Tên đăng nhập đã tồn tại. ',
            'name.required' => 'Tên nhân viên là trường bắt buộc. ',
            'email.required' => 'Email là trường bắt buộc. ',
            'email.unique' => 'Email đã tồn tại. ',
            'email.email' => 'Email không đúng định dạng. ',
            'phone.required' => 'Số điện thoại là trường bắt buộc. ',
            'phone.unique' => 'Số điện thoại đã tồn tại. ',
        ]);
        $fileUrl = '';
        if ($request->hasFile("attachment")) {
            $file = $request->file("attachment");
            // Kiểm tra file hợp lệ
            if ($file->isValid()) {
                // Lấy phần mở rộng của file
                $extension = $file->getClientOriginalExtension();

                // Chỉ cho phép các file PDF, Excel
                if (!in_array($extension, ['pdf', 'xls', 'xlsx'])) {
                    return response()->json(['error' => 'Định dạng file không hợp lệ!'], 422);
                }

                // Tạo tên file duy nhất
                $fileName = time() . '_' . uniqid() . '.' . $extension;

                // Đường dẫn thư mục lưu trữ trong `public/uploads/users/`
                $folderPath = 'uploads/users/' . date('Y') . '/' . date('m') . '/' . date('d');
                $destinationPath = public_path($folderPath);

                // Kiểm tra và tạo thư mục nếu chưa tồn tại
                if (!file_exists($destinationPath)) {
                    mkdir($destinationPath, 0755, true);
                }

                // Di chuyển file vào thư mục public
                $file->move($destinationPath, $fileName);

                // Đường dẫn truy cập file từ trình duyệt
                $fileUrl = "$folderPath/$fileName";

                // Lưu đường dẫn vào database
                $fileUrl = $fileUrl;
            }
        }
        $user->update([
            'account' => request('account'),
            'name' => request('name'),
            'address' => request('address'),
            'gender' => request('gender'),
            'phone' => request('phone'),
            'birthday' => request('birthday'),
            'email' => request('email'),
            'password' => request('password') ? bcrypt(request('password')) : $user->password,
            'attachment' => !empty($fileUrl) ? $fileUrl : $user->attachment,
            'update_at' => Carbon::now(),
        ]);
        $user->roles()->sync($request->role_id);
        $this->logActivity('update', User::class, $user);
        return response()->json(['message' => 'Cập nhập nhân viên thành công!', 'user' => new UserResource($user)]);
    }


    public function destroy($id)
    {
        User::find($id)->delete();
        RoleUser::where('user_id', $id)->delete();
        return response()->json(['message' => 'Xóa nhân viên thành công!']);
    }
    public function show($id)
    {
        $user = User::find($id);
        return response()->json(['user' => new UserResource($user)]);
    }
}
