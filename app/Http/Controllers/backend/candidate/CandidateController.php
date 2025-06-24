<?php

namespace App\Http\Controllers\backend\candidate;

use \Log;
use App\Http\Controllers\Controller;
use App\Http\Resources\backend\candidate\CandidateCollection;
use App\Http\Resources\backend\candidate\CandidateResource;
use App\Models\Candidate;
use App\Models\Configuration;
use App\Traits\LogsActivity;
use App\Models\CandidateUser;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CandidateController extends Controller
{
    use LogsActivity;

    public function lists(Request $request)
    {
        $keyword = $request->input('keyword');
        $user = auth()->user();
        $data = Candidate::select('id', 'full_name', 'code')
            ->when(!$user->can('candidates_all'), function ($query) use ($user) {
                return $query->where('created_by', $user->id);
            })
            ->when($keyword, function ($query) use ($keyword) {
                $query->where('id', 'like', "%{$keyword}%")
                    ->orWhere('full_name', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%")
                    ->orWhere('code', 'like', "%{$keyword}%");
            })
            ->get()
            ->map(function ($candidate) {
                return [
                    'id' => $candidate->id,
                    'full_name' => "{$candidate->code} - {$candidate->full_name}",
                ];
            });
        return response()->json(['candidates' => $data]);
    }
    public function search(Request $request)
    {
        $user = auth()->user();
        $keyword = $request->input('keyword');
        $data = Candidate::select('id', 'full_name', 'code')
            ->when(!$user->can('candidates_all'), function ($query) use ($user) {
                return $query->where('created_by', $user->id);
            })
            ->when(
                $keyword,
                fn($query) => $query->where('id', 'like', "%{$keyword}%")
                    ->orWhere('full_name', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%")
                    ->orWhere('code', 'like', "%{$keyword}%")
            )
            ->get()->map(function ($candidate) {
                return [
                    'id' => $candidate->id,
                    'name' => "{$candidate->code} - {$candidate->full_name}",
                ];
            });
        return response()->json(['candidates' => $data]);
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $perPage = env('PER_PAGE', 20);
        $keyword = $request->input('keyword');
        $industry_id = $request->input('industry_id');
        $created_by = $request->input('created_by');
        $language = $request->input('language'); // Thêm bộ lọc ngoại ngữ
        $desired_locations = $request->input('desired_locations'); // Thêm bộ lọc khu vực mong muốn (mảng)
        $data = Candidate::with('industry:id,title')->with('createBy:id,name')->with('users')->orderBy('id', 'desc')
            ->when(
                $keyword,
                fn($query) => $query->where('id', 'like', "%{$keyword}%")
                    ->orWhere('full_name', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%")
                    ->orWhere('code', 'like', "%{$keyword}%")
            )
            ->when(
                $industry_id,
                fn($query) => $query->where('industry_id', $industry_id)
            )
            ->when(
                $created_by,
                fn($query) => $query->where('created_by', $created_by)
            )
            ->when(
                $language,
                fn($query) => $query->where('language', $language)
            )
            ->when(
                !empty($desired_locations),
                function ($query) use ($desired_locations) {
                    $array_desired_locations = explode(',', $desired_locations);
                    $array_desired_locations = array_map('intval', $array_desired_locations);
                    $query->whereHas('desiredLocations', function ($q) use ($array_desired_locations) {
                        $q->whereIn('location_id', $array_desired_locations);
                    });
                }
            )
            ->when(!$user->can('candidates_all'), function ($query) use ($user) {
                return $query->where('created_by', $user->id);
            });
            $data = $data->paginate($perPage);

            // Ẩn - Hiện: Thông tin nếu không phải người tạo, hoặc chưa được admin phân quyền
            if( $user->can('candidates_all') && !$user->can('candidates_administrator') ){
                $data = $data->through(function ($item) use ($user) {
                    if ($item->created_by !== $user->id) {
                        $item->email = $this->maskEmail($item->email);
                        $item->phone = $this->maskPhone($item->phone);
                    }
                    return $item;
                });
            }
        return response()->json(new CandidateCollection($data));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'required|unique:candidates',
            'email' => 'required|email|max:255|unique:candidates',
            'industry_id' => 'required|exists:industries,id|gt:0',
            'current_location' => 'required',
            'desired_location' => 'required',
            'cv_no_contact' => 'nullable|file|mimes:pdf|max:10240',
            'cv_with_contact' => 'nullable|file|mimes:pdf|max:10240',
            'education' => 'nullable',
            'language' => 'nullable',
            'language_other' => 'nullable',
            'experience_summary' => 'nullable',
        ], [
            'full_name.required' => 'Họ và tên là trường bắt buộc. ',
            'phone.required' => 'Số điện thoại là trường bắt buộc. ',
            'phone.unique' => 'Số điện thoại đã tồn tại. ',
            'email.required' => 'Email là trường bắt buộc. ',
            'email.email' => 'Email không đúng định dạng. ',
            'email.unique' => 'Email đã tồn tại. ',
            'industry_id.required' => 'Nhóm ngành nghề là trường bắt buộc. ',
            'industry_id.gt' => 'Nhóm ngành nghề là trường bắt buộc. ',
            'industry_id.exists' => 'Nhóm ngành nghề không tồn tại. ',
            'current_location.required' => 'Chỗ ở hiện tại là trường bắt buộc. ',
            'desired_location.required' => 'Khu vực mong muốn làm việc là trường bắt buộc. ',
            'cv_no_contact.mimes' => 'File CV không có thông tin liên hệ không đúng định dạng. ',
            'cv_with_contact.mimes' => 'File CV có thông tin liên hệ không đúng định dạng. ',
            'cv_no_contact.max' => 'Dung lượng File CV không có thông tin liên hệ không quá 10MB. ',
            'cv_with_contact.max' => 'Dung lượng File CV không có thông tin liên hệ không quá 10MB. ',
        ]);
        $lastCustomer = Candidate::orderBy('id', 'desc')->first();
        if ($lastCustomer) {
            $lastCode = (int) filter_var($lastCustomer->code, FILTER_SANITIZE_NUMBER_INT); // Lấy số từ mã KHxxx
            $newCode = 'UV' . str_pad($lastCode + 1, 3, '0', STR_PAD_LEFT); // Tăng lên 1 và định dạng 3 chữ số
        } else {
            $newCode = 'UV001'; // Nếu chưa có khách hàng nào, bắt đầu từ KH001
        }
        $data['code'] = $newCode;
        $data['created_by'] = Auth::user()->id;
        $expiration_date = Configuration::where('key', 'candidate.expiration_date')->value('value');
        $data['expiry_date'] = Carbon::now()->addDays($expiration_date ? (int) $expiration_date : 90);
        // Xử lý upload file vào thư mục public/uploads/cv
        if ($request->hasFile('cv_no_contact')) {
            $data['cv_no_contact'] = $this->uploadFile($request->file('cv_no_contact'));
        }
        if ($request->hasFile('cv_with_contact')) {
            $data['cv_with_contact'] = $this->uploadFile($request->file('cv_with_contact'));
        }
        $candidate = Candidate::create($data);
        $desired_location = json_decode($request->input('desired_location'));
        foreach ($desired_location as $location) {
            $candidate->desiredLocations()->create(['location_id' => $location]);
        }
        $this->logActivity('create', Candidate::class, $candidate);
        return response()->json([
            'message' => 'Thêm mới ứng viên thành công',
            'candidate' => new CandidateResource($candidate->load('desiredLocations'))
        ]);
    }
    private function uploadFile($file)
    {
        $folderPath = 'uploads/cv/' . now()->format('Y/m/d');
        $destinationPath = public_path($folderPath);

        // Tạo thư mục nếu chưa tồn tại
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }
        // Đặt tên file ngẫu nhiên tránh trùng lặp
        $fileName = Str::random(10) . '.' . $file->getClientOriginalExtension();
        // Di chuyển file vào thư mục public
        $file->move($destinationPath, $fileName);

        return "$folderPath/$fileName"; // Lưu đường dẫn file để lưu vào database
    }
    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $candidate = Candidate::where(['id' => $id])
            ->when(( $user->can('candidates_all') && !$user->can('candidates_administrator') ), function ($query) use ($user) {
                return $query->where('created_by', $user->id);
            })
            ->first();
        if (empty($candidate)) {
            return response()->json(['message' => 'Ứng viên không tồn tại'], 404);
        }
        $data = $request->validate([
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:candidates,phone,' . $candidate->id,
            'email' => 'required|email|max:255|unique:candidates,email,' . $candidate->id,
            'industry_id' => 'required|exists:industries,id|gt:0',
            'current_location' => 'required',
            'desired_location' => 'required',
            'cv_no_contact' => 'nullable|file|mimes:pdf|max:10240',
            'cv_with_contact' => 'nullable|file|mimes:pdf|max:10240',
            'education' => 'nullable',
            'language' => 'nullable',
            'language_other' => 'nullable',
            'experience_summary' => 'nullable',
        ], [
            'full_name.required' => 'Họ và tên là trường bắt buộc. ',
            'phone.required' => 'Số điện thoại là trường bắt buộc. ',
            'phone.unique' => 'Số điện thoại đã tồn tại. ',
            'email.required' => 'Email là trường bắt buộc. ',
            'email.email' => 'Email không đúng định dạng. ',
            'email.unique' => 'Email đã tồn tại. ',
            'industry_id.required' => 'Nhóm ngành nghề là trường bắt buộc. ',
            'industry_id.gt' => 'Nhóm ngành nghề là trường bắt buộc. ',
            'industry_id.exists' => 'Nhóm ngành nghề không tồn tại. ',
            'current_location.required' => 'Chỗ ở hiện tại là trường bắt buộc. ',
            'desired_location.required' => 'Khu vực mong muốn làm việc là trường bắt buộc. ',
            'cv_no_contact.mimes' => 'File CV không có thông tin liên hệ không đúng định dạng. ',
            'cv_with_contact.mimes' => 'File CV có thông tin liên hệ không đúng định dạng. ',
            'cv_no_contact.max' => 'Dung lượng File CV không có thông tin liên hệ không quá 10MB. ',
            'cv_with_contact.max' => 'Dung lượng File CV không có thông tin liên hệ không quá 10MB. ',
        ]);
        if ($request->hasFile('cv_no_contact')) {
            $data['cv_no_contact'] = $this->uploadFile($request->file('cv_no_contact'));
        } else {
            $data['cv_no_contact'] = $candidate->cv_no_contact;
        }
        if ($request->hasFile('cv_with_contact')) {
            $data['cv_with_contact'] = $this->uploadFile($request->file('cv_with_contact'));
        } else {
            $data['cv_with_contact'] = $candidate->cv_with_contact;
        }
        $candidate->update($data);
        $candidate->desiredLocations()->delete();
        $desired_location = json_decode($request->input('desired_location'));
        foreach ($desired_location as $location) {
            $candidate->desiredLocations()->create(['location_id' => $location]);
        }
        $this->logActivity('update', Candidate::class, $candidate);
        return response()->json([
            'message' => 'Cập nhật ứng viên thành công',
            'candidate' => new CandidateResource($candidate)
        ]);
    }
    public function show($id)
    {
        $user = auth()->user();
        $candidate = Candidate::where(['id' => $id])
            ->when(!$user->can('candidates_all'), function ($query) use ($user) {
                return $query->where('created_by', $user->id);
            })
            ->first();

        // Ẩn - Hiện: Thông tin nếu không phải người tạo, hoặc chưa được admin phân quyền
        if ($candidate->created_by !== $user->id) {
            $candidate->email = $this->maskEmail($candidate->email);
            $candidate->phone = $this->maskPhone($candidate->phone);
        }

        if (empty($candidate)) {
            return response()->json(['message' => 'Ứng viên không tồn tại'], 404);
        }
        return response()->json(['message' => 'successfully', 'candidate' => new CandidateResource($candidate)]);
    }

    public function destroy($id)
    {
        $user = auth()->user();
        $candidate = Candidate::where(['id' => $id])
            ->when(!$user->can('candidates_all'), function ($query) use ($user) {
                return $query->where('created_by', $user->id);
            })
            ->first();
        if (empty($candidate)) {
            return response()->json(['message' => 'Ứng viên không tồn tại'], 404);
        }
        $this->logActivity('delete', Candidate::class, $candidate);
        $candidate->delete();
        return response()->json(['message' => 'Xóa ứng viên thành công']);
    }

    public function addUserForCandidate(Request $request)
    {
        $candidateID = (int)$request->candidate_id;
        $users = $request->users;
        if( $candidateID > 0 && isset($users) && is_array($users) && count($users) ){
            $userIds = collect($users)->pluck('id')->toArray();
            // Xoá bỏ những nhân viên không nằm trong mảng
            CandidateUser::whereNotIn('user_id', $userIds)->where('candidate_id', $candidateID)->delete();
            // Lưu những thông tin mới
            foreach ( $userIds as $userid ){
                $_data = [
                    'user_id' => $userid,
                    'candidate_id' => $candidateID,
                ];
                CandidateUser::create($_data);
            }
            return response()->json(['message' => 'Tạo thông tin thành công!']);
        } else {
            return response()->json(['message' => 'Thông tin không chính xác!']);
        }
    }

    public function maskPhone($phone)
    {
        if (strlen($phone) < 4) return str_repeat('x', strlen($phone));
        return substr($phone, 0, 2) . str_repeat('x', strlen($phone) - 4) . substr($phone, -2);
    }

    public function maskEmail($email)
    {
        $parts = explode('@', $email);
        if (count($parts) !== 2) return str_repeat('*', strlen($email));
    
        $name = $parts[0];
        $domain = $parts[1];
    
        $visible = max(1, floor(strlen($name) / 3));
        return substr($name, 0, $visible) . str_repeat('*', strlen($name) - $visible) . '@' . $domain;
    }
    
}
