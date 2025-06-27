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
        $data = $request->validate([
            'title' => ['required', 'array'],
            'title.vi' => ['required', 'string', Rule::unique('industries', 'title->vi')],
        ], [
            'title.vi.required' => 'Tiêu đề là bắt buộc.',
            'title.vi.unique' => 'Tiêu đề đã tồn tại.',
        ]);
        $industry = Industry::create([
            'title' => $titles['vi'],
            'created_by' => Auth::user()->id,
        ]);
        // Thêm phần dịch
        unset($titles['vi']);
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
        $industry = Industry::findOrFail($id);
        // $data = $request->validate([
        //     'title' => 'required|string|max:255|unique:industries,title,' . $industry->id,
        // ], [
        //     'title.required' => 'Tiêu đề là trường bắt buộc.',
        //     'title.unique' => 'Tiêu đề đã tồn tại.',
        // ]);

        $data = $request->validate([
            'title' => ['required', 'array'],
            'title.vi' => ['required', 'string', 'unique:industries,title,'. $industry->id],
        ], [
            'title.vi.required' => 'Tiêu đề là bắt buộc.',
            'title.vi.unique' => 'Tiêu đề đã tồn tại.',
        ]);

        $industry->update($data);
        $this->logActivity('update', Industry::class, $industry);
        return response()->json([
            'message' => 'Cập nhật nhóm ngành nghề thành công',
            'industry' => new IndustryResource($industry)
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
