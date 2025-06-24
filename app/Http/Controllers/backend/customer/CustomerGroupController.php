<?php

namespace App\Http\Controllers\backend\customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\backend\customer\CustomerGroupCollection;
use App\Http\Resources\backend\customer\CustomerGroupResource;
use App\Models\CustomerGroup;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerGroupController extends Controller
{
    use LogsActivity;
    public function lists()
    {
        $data = CustomerGroup::select('id', 'title')->get();
        return response()->json(['groups' => $data]);
    }
    public function index(Request $request)
    {
        $perPage = env('PER_PAGE');
        $keyword = $request->input('keyword');
        $data = CustomerGroup::with('customers')->orderBy('id', 'desc')->when(
            $keyword,
            fn($query) =>
            $query->where(
                fn($q) =>
                $q->where('title', 'like', "%{$keyword}%")
            )
        );
        $data = $data->paginate($perPage);
        return response()->json(new CustomerGroupCollection($data));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|unique:customer_groups|string|max:255',
        ], [
            'title.required' => 'Tiêu đề là trường bắt buộc. ',
            'title.unique' => 'Tiêu đề đã tồn tại. '
        ]);
        $data['created_by'] = Auth::user()->id;
        $group = CustomerGroup::create($data);
        $this->logActivity('create', CustomerGroup::class, $group);
        return response()->json(['message' => 'Thêm mới nhóm khách hàng thành công', 'group' => new CustomerGroupResource($group)]);
    }

    public function update(Request $request, $id)
    {
        $group = CustomerGroup::findOrFail($id);
        $data = $request->validate([
            'title' => 'required|string|max:255|unique:customer_groups,title,' . $group->id,
        ], [
            'title.required' => 'Tiêu đề là trường bắt buộc.',
            'title.unique' => 'Tiêu đề đã tồn tại.'
        ]);
        $group->update($data);
        $this->logActivity('update', CustomerGroup::class, $group);
        return response()->json([
            'message' => 'Cập nhật nhóm khách hàng thành công',
            'group' => new CustomerGroupResource($group)
        ]);
    }

    public function destroy($id)
    {
        $group = CustomerGroup::findOrFail($id);
        $this->logActivity('delete', CustomerGroup::class, $group);
        $group->delete();
        return response()->json(['message' => 'Deleted']);
    }
}
