<?php

namespace App\Http\Controllers\backend\industry;

use App\Http\Controllers\Controller;
use App\Http\Resources\backend\industry\IndustryCollection;
use App\Http\Resources\backend\industry\IndustryResource;
use App\Models\Industry;
use App\Traits\LogsActivity;
use App\Models\IndustryTranslation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class IndustryController extends Controller
{
    use LogsActivity;

    public function lists()
    {
        $data = Industry::select('id', 'title')->get();
        return response()->json(['industries' => $data]);
    }
    
    public function listsLang()
    {
        $data = Industry::select('id', 'title')->with('industry_translations')->get();
        $result = [];

        foreach ($data as $industry) {
            foreach ($industry->industry_translations as $translation) {
                $lang = $translation->alanguage;
                $result[$lang][] = [
                    'id' => $industry->id,
                    'title' => $translation->title,
                ];
            }
        }
        return response()->json(['industries' => $result]);
    }

    public function index(Request $request)
    {
        $perPage = env('PER_PAGE', 20);
        $keyword = $request->input('keyword');
        $data = Industry::orderBy('id', 'desc')->with('createBy:id,name')->when(
            $keyword,
            fn($query) => $query->where('title', 'like', "%{$keyword}%")
        );
        $data = $data->paginate($perPage);
        return response()->json(new IndustryCollection($data));
    }

    public function store(Request $request)
    {
        $titles = $request->title;
        $languages = array_keys(config('languages'));
        foreach ($languages as $lang) {
            $rules["title.$lang"] = 'required|string';
            $rules["title.vi"] = 'unique:industries,title';
        }
        $messages = [
            'title.*.required' => 'Tiêu đề là bắt buộc.',
            'title.vi.unique' => 'Tiêu đề đã tồn tại.',
        ];

        $data = $request->validate($rules, $messages);
        $industry = Industry::create([
            'title' => $titles['vi'],
            'created_by' => Auth::user()->id,
        ]);
        // Thêm phần dịch
        if( isset($titles) && is_array($titles) && count($titles) ) {
            foreach( $titles as $key => $title ){
                IndustryTranslation::create([
                    'alanguage' => $key,
                    'title' => $title,
                    'industry_id' => $industry->id,
                ]);
            }
        }

        $this->logActivity('create', Industry::class, $industry);
        return response()->json([
            'message' => 'Thêm mới nhóm ngành nghề thành công',
            'industry' => new IndustryResource($industry)
        ]);
    }

    public function update(Request $request, $id)
    {
        $titles = $request->title;
        $industry = Industry::with('industry_translations')->findOrFail($id);
        $languages = array_keys(config('languages'));
        $rules = [];
        foreach ($languages as $lang) {
            $rules["title.$lang"] = 'required|string';
            $rules["title.vi"] = 'unique:industries,title,'.$industry->id;
        }
        $messages = [
            'title.*.required' => 'Tiêu đề là bắt buộc.',
            'title.vi.unique' => 'Tiêu đề đã tồn tại.',
        ];

        $data = $request->validate($rules, $messages);
        $industry->update([
            'title' => $titles['vi'],
        ]);
        // Thêm phần dịch
        IndustryTranslation::where(['industry_id' => $id])->delete();
        if( isset($titles) && is_array($titles) && count($titles) ) {
            foreach( $titles as $key => $title ){
                IndustryTranslation::create([
                    'alanguage' => $key,
                    'title' => $title,
                    'industry_id' => $industry->id,
                ]);
            }
        }
        $this->logActivity('update', Industry::class, $industry);
        return response()->json([
            'message' => 'Cập nhật nhóm ngành nghề thành công',
            'industry' => new IndustryResource(Industry::with('industry_translations')->findOrFail($id))
        ]);
    }

    public function destroy($id)
    {
        $industry = Industry::findOrFail($id);
        $this->logActivity('delete', Industry::class, $industry);
        $industry->delete();
        return response()->json(['message' => 'Xóa nhóm ngành nghề thành công']);
    }
}
