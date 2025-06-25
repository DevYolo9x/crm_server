<?php

namespace App\Http\Controllers\backend\job;

use App\Http\Controllers\Controller;
use App\Http\Resources\backend\job\JobCollection;
use App\Http\Resources\backend\job\JobResource;
use App\Models\Configuration;
use App\Models\Job;
use App\Models\JobLocation;
use App\Models\JobUser;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class JobController extends Controller
{
    use LogsActivity;

    public function status()
    {
        return response()->json(['status' => config('job_status.statuses')]);
    }
    public function index(Request $request)
    {
        $user = auth()->user();
        $perPage = env('PER_PAGE');
        $keyword = $request->input('keyword');
        $candidateId = $request->input('candidate_id');
        $locationId = $request->input('location_id');
        $userId = $request->input('user_id'); // Thêm bộ lọc user_id
        $data = Job::with(['customer:id,name,code', 'user:id,name,code', 'candidates:id,full_name', 'locations.province'])->orderBy('id', 'desc')
            ->when(!$user->can('jobs_all'), function ($query) use ($user) {
                return $query->where('created_by', $user->id);
            })
            ->when(
                $keyword,
                fn($query) =>
                $query->where(
                    fn($q) =>
                    $q->where('job_title', 'like', "%{$keyword}%")
                        ->orWhere('position', 'like', "%{$keyword}%")->orWhere('company_info', 'like', "%{$keyword}%")->orWhere('job_description', 'like', "%{$keyword}%")->orWhere('requirements', 'like', "%{$keyword}%")->orWhere('benefits', 'like', "%{$keyword}%")->orWhere('additional_info', 'like', "%{$keyword}%")->orWhere('id', 'like', "%{$keyword}%")
                )->orWhereHas(
                    'customer',
                    fn($q) =>
                    $q->where('code', 'like', "%{$keyword}%")->orWhere('name', 'like', "%{$keyword}%")->orWhere('phone', 'like', "%{$keyword}%")->orWhere('tax_code', 'like', "%{$keyword}%") // Thay 'item_name' bằng cột bạn muốn tìm kiếm
                )
            )
            ->when(!empty($userId), function ($query) use ($userId) {
                $query->whereHas('users', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                });
            })
            ->when(!empty($locationId), function ($query) use ($locationId) {
                $query->whereHas('locations', function ($q) use ($locationId) {
                    $q->where('province_id', $locationId);
                });
            })
            ->when(!empty($candidateId), function ($query) use ($candidateId) {
                $query->orWhereHas('candidates', function ($q) use ($candidateId) {
                    $q->where('candidate_id', $candidateId);
                });
            })
            ->when(
                !empty($request->customer_id),
                fn($query) =>
                $query->where('customer_id', $request->customer_id)
            )->when(
                !empty($request->status),
                fn($query) =>
                $query->where('status', $request->status)
            )
            ->when(
                !empty($request->created_by),
                fn($query) =>
                $query->where('created_by', $request->created_by)
            );
        $data = $data->paginate($perPage);
        return response()->json(new JobCollection($data));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'job_title' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'company_info' => 'required',
            'job_description' => 'required',
            'requirements' => 'required',
            'benefits' => 'required',
            'additional_info' => 'nullable',
            'status' => 'required',
            'locations' => 'required', // Thêm validation cho locations
            'users' => 'required',
        ], [
            'customer_id.required' => 'Khách hàng là trường bắt buộc. ',
            'job_title.required' => 'Tiêu đề JOB là trường bắt buộc. ',
            'position.required' => 'Vị trí tuyển dụng là trường bắt buộc. ',
            'locations.required' => 'Địa điểm làm việc là trường bắt buộc. ',
            'company_info.required' => 'Thông tin công ty là trường bắt buộc. ',
            'job_description.required' => 'Mô tả công việc là trường bắt buộc. ',
            'requirements.required' => 'Yêu cầu vị trí là trường bắt buộc. ',
            'benefits.required' => 'Chế độ phúc lợi là trường bắt buộc. ',
            'additional_info.required' => 'Thông tin khác là trường bắt buộc. ',
            'status.required' => 'Trạng thái là trường bắt buộc. ',
            'users.required' => 'Nhân viên phục trách là trường bắt buộc. ',

        ]);
        $data['created_by'] = Auth::user()->id;
        $job = Job::create($data);
        // Lưu locations vào bảng job_locations
        $locations = json_decode($request->input('locations'));
        $users = json_decode($request->input('users'));
        foreach ($locations as $province_id) {
            JobLocation::create([
                'job_id' => $job->id,
                'province_id' => $province_id,
            ]);
        }
        foreach ($users as $user_id) {
            JobUser::create([
                'job_id' => $job->id,
                'user_id' => $user_id,
            ]);
        }
        $this->logActivity('create', Job::class, $job);
        return response()->json(['message' => 'Thêm mới job order thành công', 'job' => new JobResource($job)]);
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $job = Job::with(['locations'])->where(['id' => $id])
            ->when(!$user->can('jobs_all'), function ($query) use ($user) {
                return $query->where('created_by', $user->id);
            })
            ->first();
        if (empty($job)) {
            return response()->json(['message' => 'job order không tồn tại'], 404);
        }
        $data = $request->validate([
            'customer_id' => 'nullable|exists:customers,id',
            'job_title' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'company_info' => 'required',
            'job_description' => 'required',
            'requirements' => 'required',
            'benefits' => 'required',
            'additional_info' => 'nullable',
            'status' => 'required',
            'locations' => 'required',
            'users' => 'required', // Validate users
        ], [
            'customer_id.required' => 'Khách hàng là trường bắt buộc. ',
            'job_title.required' => 'ID JOB là trường bắt buộc. ',
            'position.required' => 'Vị trí tuyển dụng là trường bắt buộc. ',
            'locations.required' => 'Địa điểm làm việc là trường bắt buộc. ',
            'company_info.required' => 'Thông tin công ty là trường bắt buộc. ',
            'job_description.required' => 'Mô tả công việc là trường bắt buộc. ',
            'requirements.required' => 'Yêu cầu vị trí là trường bắt buộc. ',
            'benefits.required' => 'Chế độ phúc lợi là trường bắt buộc. ',
            'additional_info.required' => 'Thông tin khác là trường bắt buộc. ',
            'status.required' => 'Trạng thái là trường bắt buộc. ',
            'users.required' => 'Nhân viên phục trách là trường bắt buộc. ',

        ]);

        $job->update($data);
        // Xóa locations cũ và thêm locations mới
        $job->locations()->delete();
        $job->users()->delete();
        $locations = json_decode($request->input('locations'));
        $users = json_decode($request->input('users'));
        foreach ($locations as $province_id) {
            JobLocation::create([
                'job_id' => $job->id,
                'province_id' => $province_id,
            ]);
        }
        foreach ($users as $user_id) {
            JobUser::create([
                'job_id' => $job->id,
                'user_id' => $user_id,
            ]);
        }
        $this->logActivity('update', Job::class, $job);
        return response()->json(['message' => 'Cập nhập job order thành công', 'job' => new JobResource($job->load(['locations', 'users'])), 'status' => $request->status]);
    }

    public function show($id)
    {
        $user = auth()->user();
        $job = Job::with(['locations.province', 'users.user'])->where(['id' => $id])
            ->when(!$user->can('jobs_all'), function ($query) use ($user) {
                return $query->where('created_by', $user->id);
            })
            ->first();
        if (empty($job)) {
            return response()->json(['message' => 'job order không tồn tại'], 404);
        }
        return response()->json(['message' => 'successfully', 'job' => new JobResource($job)]);
    }
    public function destroy($id)
    {
        $user = auth()->user();
        $job = Job::where(['id' => $id])
            ->when(!$user->can('jobs_all'), function ($query) use ($user) {
                return $query->where('created_by', $user->id);
            })
            ->first();
        if (empty($job)) {
            return response()->json(['message' => 'job order không tồn tại'], 404);
        }
        $this->logActivity('delete', Job::class, $job);
        $job->delete();
        return response()->json(['message' => 'Xóa job order thành công']);
    }


    public function exportPdf($id)
    {
        $job = Job::with(['customer:id,code,name,phone,email', 'locations.province', 'users'])->findOrFail($id);
        // Lấy logo và chuyển sang base64
        $logoPath = optional(Configuration::where('key', 'general.logo')->first())->value;
        $logoBase64 = null;
        if ($logoPath && file_exists(public_path($logoPath))) {
            $fullPath = public_path($logoPath);
            $type = pathinfo($fullPath, PATHINFO_EXTENSION);
            $imageData = file_get_contents($fullPath);
            $logoBase64 = 'data:image/' . $type . ';base64,' . base64_encode($imageData);
        }
        $data = [
            'job' => $job,
            'position' => $job->position,
            'company_info' => $job->company_info,
            'job_description' => $job->job_description,
            'requirements' => $job->requirements,
            'benefits' => $job->benefits,
            'locations' => $job->locations->pluck('province.name')->toArray(),
            'users' => $job->users->pluck('name')->toArray(),
            'customer_name' => $job->customer->name ?? 'N/A',
            'customer_email' => $job->customer->email ?? 'N/A',
            'customer_phone' => $job->customer->phone ?? 'N/A',
            'logo' => $logoBase64,
            'status' => $job->status,

        ];
        $pdf = Pdf::loadView('pdf.job', $data);
        return $pdf->download('JD_' . $job->job_title . '.pdf');
    }
}
