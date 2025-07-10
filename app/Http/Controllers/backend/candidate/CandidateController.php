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

// Export word
use PhpOffice\PhpWord\TemplateProcessor;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html;
use App\Helpers\HtmlToText;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\File;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\SimpleType\Jc;

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

        return response()->json(new CandidateCollection($data));
    }

    public function store(UpdateCandidateRequest $request)
    {
        $timeEducation = json_decode($request->time_education, true);
        $skills = json_decode($request->skills, true);
        $workExperience = json_decode($request->work_experience, true);
        $strength = json_decode($request->currentStrength, true);
        $gender = json_decode($request->gender, true);

        $languages = array_keys(config('languages'));
        $validated = $request->validated(); // Validate dữ liệu
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

        // Upload Avarta
        $avatar = '';
        if ($request->hasFile("avatar")) {
            $avatar = $this->uploadFile($request->file("avatar"));
        }

        // Tạo thông tin ứng viên
        $_create = [
            'code' => $newCode,
            'created_by' => Auth::user()->id,
            'expiry_date' => Carbon::now()->addDays($expiration_date ? (int) $expiration_date : 90),
            'full_name' => $request->full_name['vi'],
            'phone' => $request->phone,
            'email' => $request->email,
            'birthday' => $request->birthday,
            'current_location' => '',
            'cv_no_contact' => '',
            'avatar' => $avatar,
            'cv_with_contact' => '',
            'language_other' => '',
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
                'time_education' => isset($timeEducation[$lang]) ? json_encode($timeEducation[$lang]) : '',
                'skills' => isset($skills[$lang]) ? json_encode($skills[$lang]) : '',
                'work_experience' => isset($workExperience[$lang]) ? json_encode($workExperience[$lang]) : '',
                'strength' => $strength[$lang] ?? '',
                'gender' => $gender[$lang] ?? '',
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
        //return response()->json($request->birthday);
        $timeEducation = json_decode($request->time_education, true);
        $skills = json_decode($request->skills, true);
        $workExperience = json_decode($request->work_experience, true);
        $strength = json_decode($request->currentStrength, true);
        $gender = json_decode($request->gender, true);
        //return response()->json($gender);

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
        $validated = $request->validated(); // Validate dữ liệu

        // Upload file cv lên serve
        foreach ($languages as $lang) {
            // CV không có thông tin liên hệ
            if ($request->hasFile("file_cv.$lang.cv_no_contact.file")) {
                $data["file_cv.$lang.cv_no_contact.file"] = $this->uploadFile($request->file("file_cv.$lang.cv_no_contact.file"));
            } else {
                $data["file_cv.$lang.cv_no_contact.file"] = $candidate->file_cv[$lang]['cv_no_contact']['file'] ?? null;
            }
            // CV có thông tin liên hệ
            if ($request->hasFile("file_cv.$lang.cv_with_contact.file")) {
                $data["file_cv.$lang.cv_with_contact.file"] = $this->uploadFile($request->file("file_cv.$lang.cv_with_contact.file"));
            } else {
                $data["file_cv.$lang.cv_with_contact.file"] = $candidate->file_cv[$lang]['cv_with_contact']['file'] ?? null;
            }
        }

        // Upload Avarta
        $avatar = $candidate->avatar;
        if ($request->hasFile("avatar")) {
            $avatar = $this->uploadFile($request->file("avatar"));
        }

        // Cập nhật ứng viên
        $_update = [
            'full_name' => $request->full_name['vi'],
            'phone' => $request->phone,
            'email' => $request->email,
            'birthday' => $request->birthday,
            'avatar' => $avatar,
            'current_location' => $request->current_location,
        ];
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
            // Lấy file nếu có upload mới
            $cvNoContact = $request->hasFile("file_cv.$lang.cv_no_contact")
                ? $this->uploadFile($request->file("file_cv.$lang.cv_no_contact"))
                : null;
        
            $cvWithContact = $request->hasFile("file_cv.$lang.cv_with_contact")
                ? $this->uploadFile($request->file("file_cv.$lang.cv_with_contact"))
                : null;
            
            $detailLang = CandidateTranslation::where(['candidate_id' => $candidate->id, 'alanguage' => $lang])->first();

            $localizedData[] = [
                'candidate_id' => $candidate->id,
                'alanguage' => $lang,
                'full_name' => $request->full_name[$lang] ?? null,
                'gender' => $gender[$lang] ?? '',
                'education' => $request->education[$lang]['id'] ?? null,
                'language' => $request->language[$lang]['id'] ?? null,
                'experience_summary' => $request->experience_summary[$lang] ?? null,
                'time_education' => isset($timeEducation[$lang]) ? json_encode($timeEducation[$lang]) : '',
                'skills' => isset($skills[$lang]) ? json_encode($skills[$lang]) : '',
                'work_experience' => isset($workExperience[$lang]) ? json_encode($workExperience[$lang]) : '',
                'strength' => $strength[$lang] ?? '',
                'cv_no_contact' => $cvNoContact ?: $detailLang->cv_no_contact,
                'cv_with_contact' => $cvWithContact ?: $detailLang->cv_with_contact,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }
        $candidate->translations()->delete(); // Xoá bỏ các dịch cũ
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

    public function checkExists(Request $request)
    {
        $candidate_id = (int)$request->id;
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

    public function exportTemplateBlade(Request $request)
    {
        $id = (int)$request->id;
        $lang = $request->lang;
        $titles = config('candidate.language');

        $candidate = Candidate::with([
            'translation' => function ($query) use ($lang, $id) {
                $query->where(['candidate_id' => $id,'alanguage' => $lang]);
            }
        ])->findOrFail($id);

        // HTML danh sách strengths
        $strengthsHtml = $candidate->translation->strength ? $candidate->translation->strength : '';
        $strengthsHtml = HtmlToText::convertListToBulletParagraphs($strengthsHtml);

        $html = View::make('cv.cv_template', [
            'jobTitle' => config('candidate.language')['job'][$lang],
            'positionTitle' => config('candidate.language')['data'][$lang],
            'strengths' => $strengthsHtml,
        ])->render();

        $html = mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8');

        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Times New Roman');
        $phpWord->setDefaultFontSize(12);

        $section = $phpWord->addSection([
            'marginTop'    => 1944,
            'marginLeft'   => 720,
            'marginRight'  => 720,
            'marginBottom' => 706,
        ]);

        $header = $section->addHeader();
        $header->addImage(storage_path('app/templates/logo.png'), ['width' => 80, 'alignment' => 'left']);

        $footer = $section->addFooter();
        $footer->addPreserveText('Trang {PAGE} / {NUMPAGES}', [
            'size' => 10,
            'name' => 'Arial',
        ], ['alignment' => Jc::CENTER]);

        // Tách theo <!-- pagebreak -->
        $parts = explode('<!-- pagebreak -->', $html);

        foreach ($parts as $index => $partHtml) {
            if ($index > 0) {
                $section->addPageBreak();
            }

            // Các block
            $this->processInsertPlaceholders($section, $partHtml, $candidate, $lang);
        }

        $fileName = 'cv_' . time() . '.docx';
        $filePath = storage_path("app/public/$fileName");

        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($filePath);

        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    public function show($id)
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
        CandidateIndustry::where(['candidate_id' => $id])->delete();
        CandidateTranslation::where(['candidate_id' => $id])->delete();
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

    private function processInsertPlaceholders($section, $html, $data = [], $lang = 'vi')
    {
        //return response()->json($data);
        $translation = $data->translation;
        $placeholders = [
            'insert_personal_info_here' => fn() => HtmlToText::insertPersonalInfoBlock($section, $data, $lang),
            'insert_strengths_here'   => fn() => HtmlToText::insertStrengthsBlock($section, $translation, $lang),
            'insert_information_here' => fn() => HtmlToText::insertInformationBlock($section, $data, $lang),
            'insert_education_here'   => fn() => HtmlToText::insertEducationBlock($section, $translation, $lang),
            'insert_skills_here'      => fn() => HtmlToText::insertSkillsTable($section, $translation, $lang),
            'insert_experience_here'  => fn() => HtmlToText::insertExperienceBlock($section, $translation, $lang),
        ];
        foreach ($placeholders as $marker => $callback) {
            $fullMarker = "<!-- {$marker} -->";

            if (str_contains($html, $fullMarker)) {
                [$before, $after] = explode($fullMarker, $html, 2);
                Html::addHtml($section, $before, false, false);
                $callback();
                $html = $after;
            }
        }
        // Render phần còn lại nếu có
        if (trim($html)) {
            Html::addHtml($section, $html, false, false);
        }
    }
}
