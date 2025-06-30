<?php

namespace App\Http\Controllers\backend\candidate;

use \Log;
use \Validator;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateCandidateRequest;
use App\Http\Resources\backend\candidate\CandidateCollection;
use App\Http\Resources\backend\candidate\CandidateResource;
use App\Models\Candidate;
use App\Models\CandidateIndustry;
use App\Models\CandidateTranslation;
use App\Models\CandidateUser;
use App\Models\Configuration;
use App\Traits\LogsActivity;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;


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
        $industry_ids = $request->input('industry_id');
        $created_by = $request->input('created_by');
        $language = $request->input('language'); // Thêm bộ lọc ngoại ngữ
        $desired_locations = $request->input('desired_locations'); // Thêm bộ lọc khu vực mong muốn (mảng)
        $data = Candidate::with('industry:id,title')->with('createBy:id,name')->with('users')->with('industries')->with('translations')->orderBy('id', 'desc')
            ->when(
                $keyword,
                fn($query) => $query->where('id', 'like', "%{$keyword}%")
                    ->orWhere('full_name', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%")
                    ->orWhere('code', 'like', "%{$keyword}%")
            )
            ->when(
                !empty($industry_ids),
                fn($query) => $query->whereHas('industries', function ($q) use ($industry_ids) {
                    $q->whereIn('industries.id', $industry_ids);
                })
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
            $data = $data->through(function ($item) use ($user) {
                $hasAccess = $item->users->contains('id', $user->id);
                $canViewAll = $user->can('candidates_all');
                $isAdmin = $user->can('candidates_administrator');
                $isCreator = $item->created_by === $user->id;
            
                // Mặc định được update
                $item->permission_update = true;
            
                if ($canViewAll && !$isAdmin && !$isCreator && !$hasAccess) {
                    // Nếu không được quyền, thì ẩn thông tin và không cho update
                    $item->email = $this->maskEmail($item->email);
                    $item->phone = $this->maskPhone($item->phone);
                    $item->cv_no_contact = '';
                    $item->cv_with_contact = '';
                    $item->cv_no_contact_en = '';
                    $item->cv_with_contact_en = '';
                    $item->cv_no_contact_cn = '';
                    $item->cv_with_contact_cn = '';
                    $item->cv_no_contact_kr = '';
                    $item->cv_with_contact_kr = '';
                    $item->permission_update = false;
                }
            
                return $item;
            });
        return response()->json(new CandidateCollection($data));
    }

    public function store(Request $request)
    {
        // $data = $request->validate([
        //     'full_name' => 'required|string|max:255',
        //     'phone' => 'required|unique:candidates',
        //     'email' => 'required|email|max:255|unique:candidates',
        //     //'industry_id' => 'required|exists:industries,id|gt:0',
        //     'current_location' => 'required',
        //     'desired_location' => 'required',
        //     'cv_no_contact' => 'nullable|file|mimes:pdf|max:10240',
        //     'cv_with_contact' => 'nullable|file|mimes:pdf|max:10240',
        //     'education' => 'nullable',
        //     'language' => 'nullable',
        //     'language_other' => 'nullable',
        //     'experience_summary' => 'nullable',
        // ], [
        //     'full_name.required' => 'Họ và tên là trường bắt buộc. ',
        //     'phone.required' => 'Số điện thoại là trường bắt buộc. ',
        //     'phone.unique' => 'Số điện thoại đã tồn tại. ',
        //     'email.required' => 'Email là trường bắt buộc. ',
        //     'email.email' => 'Email không đúng định dạng. ',
        //     'email.unique' => 'Email đã tồn tại. ',
        //     //'industry_id.required' => 'Nhóm ngành nghề là trường bắt buộc. ',
        //     //'industry_id.gt' => 'Nhóm ngành nghề là trường bắt buộc. ',
        //     //'industry_id.exists' => 'Nhóm ngành nghề không tồn tại. ',
        //     'current_location.required' => 'Chỗ ở hiện tại là trường bắt buộc. ',
        //     'desired_location.required' => 'Khu vực mong muốn làm việc là trường bắt buộc. ',
        //     'cv_no_contact.mimes' => 'File CV không có thông tin liên hệ không đúng định dạng. ',
        //     'cv_with_contact.mimes' => 'File CV có thông tin liên hệ không đúng định dạng. ',
        //     'cv_no_contact.max' => 'Dung lượng File CV không có thông tin liên hệ không quá 10MB. ',
        //     'cv_with_contact.max' => 'Dung lượng File CV không có thông tin liên hệ không quá 10MB. ',
        // ]);


        $languages = array_keys(config('languages'));

        $rules = [
            'phone' => 'required|string|max:20|unique:candidates,phone',
            'email' => 'required|email|max:255|unique:candidates,email',
            'current_location' => 'required',
            'desired_location' => 'required|array',
            'cv_no_contact' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'cv_with_contact' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'language_other' => 'nullable|string|max:255',
        ];

        foreach ($languages as $lang) {
            // full_name.vi, full_name.en, full_name.kr: bắt buộc
            $rules["full_name.$lang"] = 'required|string|max:255';

            // industry_id.[lang]: bắt buộc là mảng có ít nhất 1 phần tử
            $rules["industry_id.$lang"] = 'required|array|min:1';
            $rules["industry_id.$lang.*.id"] = 'required|integer|exists:industries,id';
            $rules["industry_id.$lang.*.title"] = 'required|string|max:255';

            // education.[lang]: bắt buộc là mảng với id và name
            $rules["education.$lang"] = 'required|array';
            $rules["education.$lang.id"] = 'required|string|max:255';
            $rules["education.$lang.name"] = 'required|string|max:255';

            // language.[lang]: bắt buộc là mảng với id và name
            $rules["language.$lang"] = 'required|array';
            $rules["language.$lang.id"] = 'required|string|max:255';
            $rules["language.$lang.name"] = 'required|string|max:255';

            // experience_summary.[lang]: không bắt buộc, nhưng là chuỗi
            $rules["experience_summary.$lang"] = 'nullable|string';
        }

        $messages = [
            'full_name.*.required' => 'Tên đầy đủ [:attribute] là bắt buộc.',
            'industry_id.*.required' => 'Ngành nghề [:attribute] là bắt buộc.',
            'industry_id.*.min' => 'Phải chọn ít nhất 1 ngành nghề [:attribute].',
            'industry_id.*.*.id.required' => 'ID ngành nghề [:attribute] là bắt buộc.',
            'industry_id.*.*.id.exists' => 'ID ngành nghề [:attribute] không hợp lệ.',
            'education.*.required' => 'Trình độ học vấn [:attribute] là bắt buộc.',
            'education.*.id.required' => 'ID học vấn [:attribute] là bắt buộc.',
            'education.*.name.required' => 'Tên học vấn [:attribute] là bắt buộc.',
            'language.*.required' => 'Ngôn ngữ [:attribute] là bắt buộc.',
            'language.*.id.required' => 'ID ngôn ngữ [:attribute] là bắt buộc.',
            'language.*.name.required' => 'Tên ngôn ngữ [:attribute] là bắt buộc.',
        ];
        
        $data = $request->validate($rules, $messages);

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
        $_create = [
            'code' => $newCode,
            'created_by' => Auth::user()->id,
            'expiry_date' => Carbon::now()->addDays($expiration_date ? (int) $expiration_date : 90),
            'full_name' => $request->full_name['vi'],
            'phone' => $request->phone,
            'email' => $request->email,
            'current_location' => ' 1',
            'cv_no_contact' => ' 1',
            'cv_with_contact' => ' 1',
            'language_other' => ' 1',
        ];
        $candidate = Candidate::create($_create);
        $desired_location = $request->desired_location;
        foreach ($desired_location as $location) {
            $candidate->desiredLocations()->create(['location_id' => $location]);
        }

        // Tạo danh sách nhóm ngành nghề
        $industryIds = array_column($request->industry_id['vi'], 'id');
        $candidate->industries()->detach();
        if( isset($industryIds) && is_array($industryIds) && count($industryIds) ){
            foreach( $industryIds as $industryId ) {
                CandidateIndustry::create(['candidate_id' => $candidate->id, 'industry_id' => $industryId]);
            }
        }

        // Tạo Candidate Dịch
        $localizedData = [];
        foreach ($languages as $lang) {
            $localizedData[] = [
                'candidate_id' => $candidate->id,
                'alanguage' => $lang,
                'full_name' => $request->full_name[$lang] ?? null,
                'education' => $request->education[$lang]['id'] ?? null,
                'language' => $request->language[$lang]['id'] ?? null,
                'experience_summary' => $request->experience_summary[$lang] ?? null,
                'cv_no_contact' => '',
                'cv_with_contact' => '',
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        CandidateTranslation::insert($localizedData);

        // Cập nhật lại phân quyển button update
        $user = Auth::user();
        $candidate->permission_update = true;
        $canViewAll = $user->can('candidates_all');
        $isAdmin = $user->can('candidates_administrator');
        $isCreator = $candidate->created_by === $user->id;
        if ($canViewAll && !$isAdmin && !$isCreator) {
            $candidate->permission_update = false;
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
    public function update(UpdateCandidateRequest $request, $id)
    {
        //return response()->json($request->file('file_cv.vi.cv_no_contact'));
        // $arrDelete = CandidateIndustry::where('candidate_id', $id)->pluck('id')->toArray();
        // CandidateIndustry::whereIn('id', $arrDelete)->forceDelete();
        $user = auth()->user();
        $candidate = Candidate::where(['id' => $id])
            ->when(( $user->can('candidates_all') && !$user->can('candidates_administrator') ), function ($query) use ($user) {
                return $query->where('created_by', $user->id);
            })
            ->first();
        if (empty($candidate)) {
            return response()->json(['message' => 'Ứng viên không tồn tại'], 404);
        }
        $hasAccess = $candidate->users->contains('id', $user->id);
        $canViewAll = $user->can('candidates_all');
        $isAdmin = $user->can('candidates_administrator');
        $isCreator = $candidate->created_by === $user->id;
        if ($canViewAll && !$isAdmin && !$isCreator && !$hasAccess) {
            return response()->json(['message' => 'Bạn không có quyền truy cập'], 404);
        }


        $data = $request->all();
        $languages = array_keys(config('languages'));

        $validated = $request->validated();


        // $rules = [
        //     'phone' => 'required|string|max:20|unique:candidates,phone,' . $candidate->id,
        //     'email' => 'required|email|max:255|unique:candidates,email,' . $candidate->id,
        //     'current_location' => 'required',
        //     'desired_location' => 'required|array',
        //     'language_other' => 'nullable|string|max:255',
        //     'file_cv.vi.cv_no_contact.file' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        //     'file_cv.vi.cv_with_contact.file' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        // ];
        // foreach ($languages as $lang) {
        //     // full_name.vi, full_name.en, full_name.kr: bắt buộc
        //     $rules["full_name.$lang"] = 'required|string|max:255';
        //     // industry_id.[lang]: bắt buộc là mảng có ít nhất 1 phần tử
        //     $rules["industry_id.$lang"] = 'required|array|min:1';
        //     $rules["industry_id.$lang.*.id"] = 'required|integer|exists:industries,id';
        //     $rules["industry_id.$lang.*.title"] = 'required|string|max:255';
        //     // education.[lang]: bắt buộc là mảng với id và name
        //     $rules["education.$lang"] = 'required|array';
        //     $rules["education.$lang.id"] = 'required|string|max:255';
        //     $rules["education.$lang.name"] = 'required|string|max:255';
        //     // language.[lang]: bắt buộc là mảng với id và name
        //     $rules["language.$lang"] = 'required|array';
        //     $rules["language.$lang.id"] = 'required|string|max:255';
        //     $rules["language.$lang.name"] = 'required|string|max:255';
        //     // experience_summary.[lang]: không bắt buộc, nhưng là chuỗi
        //     $rules["experience_summary.$lang"] = 'nullable|string';
        //     // file cv
        //     $rules["file_cv.$lang.cv_no_contact.file"] = 'nullable|file|mimes:pdf,doc,docx|max:10240';
        //     $rules["file_cv.$lang.cv_with_contact.file"] = 'nullable|file|mimes:pdf,doc,docx|max:10240';
        // }
        // $messages = [
        //     'full_name.*.required' => 'Tên đầy đủ [:attribute] là bắt buộc.',
        //     'industry_id.*.required' => 'Ngành nghề [:attribute] là bắt buộc.',
        //     'industry_id.*.min' => 'Phải chọn ít nhất 1 ngành nghề [:attribute].',
        //     'industry_id.*.*.id.required' => 'ID ngành nghề [:attribute] là bắt buộc.',
        //     'industry_id.*.*.id.exists' => 'ID ngành nghề [:attribute] không hợp lệ.',
        //     'education.*.required' => 'Trình độ học vấn [:attribute] là bắt buộc.',
        //     'education.*.id.required' => 'ID học vấn [:attribute] là bắt buộc.',
        //     'education.*.name.required' => 'Tên học vấn [:attribute] là bắt buộc.',
        //     'language.*.required' => 'Ngôn ngữ [:attribute] là bắt buộc.',
        //     'language.*.id.required' => 'ID ngôn ngữ [:attribute] là bắt buộc.',
        //     'language.*.name.required' => 'Tên ngôn ngữ [:attribute] là bắt buộc.',

        //     'file_cv.vi.cv_no_contact.file.mimes' => 'File CV không có thông tin liên hệ không đúng định dạng. ',
        //     'file_cv.vi.cv_with_contact.file.mimes' => 'File CV có thông tin liên hệ không đúng định dạng. ',
        //     'file_cv.vi.cv_no_contact.file.max' => 'Dung lượng File CV không có thông tin liên hệ không quá 10MB. ',
        //     'file_cv.vi.cv_with_contact.file.max' => 'Dung lượng File CV không có thông tin liên hệ không quá 10MB. ',
        // ];
        //$data = $request->validate($rules, $messages);

        if ($request->hasFile('file_cv.vi.cv_no_contact.file')) {
            $data['file_cv.vi.cv_no_contact.file'] = $this->uploadFile($request->file('file_cv.vi.cv_no_contact.file'));
        } else {
            $data['file_cv.vi.cv_no_contact.file'] = $candidate->cv_no_contact;
        }
        if ($request->hasFile('file_cv.vi.cv_with_contact.file')) {
            $data['file_cv.vi.cv_with_contact.file'] = $this->uploadFile($request->file('file_cv.vi.cv_with_contact.file'));
        } else {
            $data['file_cv.vi.cv_with_contact.file'] = $candidate->cv_with_contact;
        }

        
        $_update = [
            'full_name' => $request->full_name['vi'],
            'phone' => $request->phone,
            'email' => $request->email,
            'current_location' => $request->current_location,
        ];
        
        //
        $candidate->update($_update);
        $candidate->desiredLocations()->delete();
        $desired_location = $request->desired_location;
        if( isset($desired_location) && is_array($desired_location) && count($desired_location) ){
            foreach ($desired_location as $location) {
                $candidate->desiredLocations()->create(['location_id' => $location]);
            }
        }

        // Tạo danh sách nhóm ngành nghề
        $industryIds = array_column($request->industry_id['vi'], 'id');
        $candidate->industries()->detach();
        if( isset($industryIds) && is_array($industryIds) && count($industryIds) ){
            foreach( $industryIds as $industryId ) {
                CandidateIndustry::create(['candidate_id' => $candidate->id, 'industry_id' => $industryId]);
            }
        }

        // Tạo Candidate Dịch
        $localizedData = [];
        foreach ($languages as $lang) {

            // Mặc định là null hoặc giữ nguyên chuỗi rỗng nếu không có file
            $cvNoContact = '';
            $cvWithContact = '';

            if ($request->hasFile("file_cv.$lang.cv_no_contact")) {
                $cvNoContact = $this->uploadFile($request->file("file_cv.$lang.cv_no_contact"));
            }
        
            if ($request->hasFile("file_cv.$lang.cv_with_contact")) {
                $cvWithContact = $this->uploadFile($request->file("file_cv.$lang.cv_with_contact"));
            }

            $localizedData[] = [
                'candidate_id' => $candidate->id,
                'alanguage' => $lang,
                'full_name' => $request->full_name[$lang] ?? null,
                'education' => $request->education[$lang]['id'] ?? null,
                'language' => $request->language[$lang]['id'] ?? null,
                'experience_summary' => $request->experience_summary[$lang] ?? null,
                'cv_no_contact' => $cvNoContact,
                'cv_with_contact' => $cvWithContact,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        CandidateTranslation::insert($localizedData);

        // Cập nhật lại phân quyển button update
        $candidate->permission_update = true;
        $hasAccess = $candidate->users->contains('id', $user->id);
        $canViewAll = $user->can('candidates_all');
        $isAdmin = $user->can('candidates_administrator');
        $isCreator = $candidate->created_by === $user->id;
        if ($canViewAll && !$isAdmin && !$isCreator && !$hasAccess) {
            $candidate->permission_update = false;
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
        $hasAccess = $candidate->users->contains('id', $user->id);
        $candidate->permission_update = true; // Mặc định cho update thông tin
        // Ẩn - Hiện: Thông tin nếu không phải người tạo, hoặc chưa được admin phân quyền
        if ($candidate->created_by !== $user->id && !$hasAccess) {
            $candidate->email = $this->maskEmail($candidate->email);
            $candidate->phone = $this->maskPhone($candidate->phone);
            $candidate->cv_no_contact = '';
            $candidate->cv_with_contact = '';
            $candidate->cv_no_contact_en = '';
            $candidate->cv_with_contact_en = '';
            $candidate->cv_no_contact_cn = '';
            $candidate->cv_with_contact_cn = '';
            $candidate->cv_no_contact_kr = '';
            $candidate->cv_with_contact_kr = '';
            $candidate->permission_update = false; // Không cho update thông tin
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
        if( $candidateID > 0 ){
            $userIds = collect($users)->pluck('id')->toArray();
            // Xoá bỏ những nhân viên
            CandidateUser::where('candidate_id', $candidateID)->delete();
            // Lưu những thông tin mới
            foreach ( $userIds as $userid ){
                $_data = [
                    'user_id' => $userid,
                    'candidate_id' => $candidateID,
                ];
                CandidateUser::create($_data);
            }
            // Lấy lại ứng viên đã cập nhật kèm danh sách user
            $candidate = Candidate::with('industry:id,title')->with('createBy:id,name')->with('users')->find($candidateID);
            return response()->json(['message' => 'Tạo thông tin thành công!', 'candidate' => new CandidateResource($candidate)]);
        } else {
            return response()->json(['message' => 'Thông tin không chính xác!']);
        }
    }

    public function checkExists(Request $request)
    {
        $candidate_id = (int)$request->candidate_id;
        $result = [
            'phone' => ['message' => '', 'status' => false],
            'email' => ['message' => '', 'status' => false],
        ];
        $rules = [
            'phone' => ['nullable', 'regex:/^0[0-9]{9}$/'],
            'email' => ['nullable', 'email'],
        ];
        $messages = [
            'phone.regex' => 'Số điện thoại phải là dạng số và gồm 10 ký tự',
            'email.email' => '(Email không đúng định dạng)',
        ];
        if (empty($candidate_id)) {
            $rules['phone'][] = Rule::unique('candidates', 'phone');
            $rules['email'][] = Rule::unique('candidates', 'email');
            $messages['phone.unique'] = 'Số điện thoại đã tồn tại';
            $messages['email.unique'] = 'Email đã tồn tại';
        }
        try {
            $validated = $request->validate($rules, $messages);
        } catch (ValidationException $e) {
            $errors = $e->errors();
            if (isset($errors['phone'])) {
                $result['phone']['status'] = true;
                $result['phone']['message'] = '('.$errors['phone'][0].')';
            }
            if (isset($errors['email'])) {
                $result['email']['status'] = true;
                $result['email']['message'] = '('.$errors['email'][0].')';
            }
            return response()->json($result);
        }
        // Validate xong, tiếp tục kiểm tra tồn tại nếu có candidate_id
        if (!empty($validated['phone'])) {
            $query = Candidate::where('phone', $validated['phone']);
            if ($candidate_id) {
                $query->where('id', '<>', $candidate_id);
            }
            $exists = $query->exists();
            $result['phone']['status'] = $exists;
            $result['phone']['message'] = $exists ? '(Số điện thoại đã tồn tại)' : '';
        }
        if (!empty($validated['email'])) {
            $query = Candidate::where('email', $validated['email']);
            if ($candidate_id) {
                $query->where('id', '<>', $candidate_id);
            }
            $exists = $query->exists();
            $result['email']['status'] = $exists;
            $result['email']['message'] = $exists ? '(Email đã tồn tại)' : '';
        }
        return response()->json($result);
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
