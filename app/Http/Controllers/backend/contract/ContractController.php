<?php

namespace App\Http\Controllers\backend\contract;

use App\Http\Controllers\Controller;
use App\Http\Resources\backend\contract\ContractCollection;
use App\Http\Resources\backend\contract\ContractResource;
use App\Models\Contract;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContractController extends Controller
{
    use LogsActivity;

    public function lists()
    {
        $user = auth()->user();
        $data = Contract::select('id', 'name')
            ->when(!$user->can('contracts_all'), function ($query) use ($user) {
                return $query->where('user_id', $user->id);
            })
            ->get();
        return response()->json(['contracts' => $data]);
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $perPage = env('PER_PAGE', 20);
        $keyword = $request->input('keyword');
        $customer_id = $request->input('customer_id');
        $created_by = $request->input('created_by');
        $sortBy = $request->input('sort_by', 'id'); // Mặc định sắp xếp theo id
        $sortDirection = $request->input('sort_direction', 'desc'); // Mặc định giảm dần

        // Danh sách các cột cho phép sắp xếp
        $allowedSortColumns = ['id', 'name', 'total_amount', 'created_at', 'warranty_end_date'];

        // Kiểm tra nếu sortBy không nằm trong danh sách cho phép thì mặc định là 'id'
        if (!in_array($sortBy, $allowedSortColumns)) {
            $sortBy = 'id';
        }
        // Kiểm tra hướng sắp xếp, chỉ cho phép 'asc' hoặc 'desc'
        $sortDirection = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';

        $data = Contract::with(['customer', 'responsiblePerson'])
            ->with(['user:id,name'])
            ->whereNull('deleted_at')
            ->when(!$user->can('contracts_all'), function ($query) use ($user) {
                return $query->where('user_id', $user->id);
            })
            ->when($keyword, function ($query) use ($keyword) {
                $query->where('id', 'like', "%{$keyword}%")
                    ->orWhere('name', 'like', "%{$keyword}%")
                    ->orWhere('total_amount', 'like', "%{$keyword}%")
                    ->orWhere('notes', 'like', "%{$keyword}%");
            })
            ->when($customer_id, function ($query) use ($customer_id) {
                $query->where('customer_id', $customer_id);
            })
            ->when($created_by, function ($query) use ($created_by) {
                $query->where('created_by', $created_by);
            })
            ->orderBy($sortBy, $sortDirection) // Sắp xếp theo cột và hướng được chỉ định
            ->paginate($perPage);

        return response()->json(new ContractCollection($data));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string',
            'customer_id' => 'required|exists:customers,id',
            'responsible_person_id' => 'required|exists:users,id',
            'warranty_end_date' => 'nullable|date',
            'invoice_date' => 'nullable|date',
            'invoice_date_2' => 'nullable|date',
            'total_amount' => 'required|numeric',
            'first_payment' => 'nullable|numeric',
            'second_payment' => 'nullable|numeric',
            'notes' => 'nullable|string',
            'products' => 'required|array',
            'products.*.product' => 'required',
            'products.*.price' => 'required|numeric',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.discount' => 'nullable|numeric',
            'products.*.tax' => 'nullable',
            'products.*.total' => 'required|numeric',
            'products.*.note' => 'nullable',
        ], [
            // Thông báo lỗi cho các trường chính
            'name.required' => 'Tên hợp đồng là bắt buộc.',
            'name.string' => 'Tên hợp đồng phải là chuỗi ký tự.',
            'customer_id.required' => 'Khách hàng là bắt buộc.',
            'customer_id.exists' => 'Khách hàng không tồn tại.',
            'responsible_person_id.required' => 'Người chịu trách nhiệm là bắt buộc.',
            'responsible_person_id.exists' => 'Người chịu trách nhiệm không tồn tại.',
            'warranty_end_date.date' => 'Ngày kết thúc bảo hành phải là định dạng ngày hợp lệ.',
            'invoice_date.date' => 'Thời gian phát hành hóa đơn phải là định dạng ngày hợp lệ.',
            'total_amount.required' => 'Số tiền hợp đồng là bắt buộc.',
            'total_amount.numeric' => 'Số tiền hợp đồng phải là số.',
            'first_payment.numeric' => 'Số tiền thu đợt 1 phải là số.',
            'second_payment.numeric' => 'Số tiền thu đợt 2 phải là số.',
            'notes.string' => 'Ghi chú phải là chuỗi ký tự.',
            'products.required' => 'Danh sách sản phẩm là bắt buộc.',
            'products.array' => 'Danh sách sản phẩm phải là một mảng.',
            // Thông báo lỗi cho các trường trong mảng products
            'products.*.product.required' => 'Tên sản phẩm là bắt buộc.',
            'products.*.price.required' => 'Giá sản phẩm là bắt buộc.',
            'products.*.price.numeric' => 'Giá sản phẩm phải là số.',
            'products.*.quantity.required' => 'Số lượng sản phẩm là bắt buộc.',
            'products.*.quantity.integer' => 'Số lượng sản phẩm phải là số nguyên.',
            'products.*.quantity.min' => 'Số lượng sản phẩm phải lớn hơn hoặc bằng 1.',
            'products.*.discount.numeric' => 'Giảm giá phải là số.',
            'products.*.total.required' => 'Thành tiền là bắt buộc.',
            'products.*.total.numeric' => 'Thành tiền phải là số.',
        ]);
        $data['created_by'] = Auth::user()->id;
        $contract = Contract::create($data);
        // Lưu danh sách sản phẩm
        $contract->products()->createMany($data['products']);
        $this->logActivity('create', Contract::class, $contract);
        return response()->json([
            'message' => 'Thêm mới hợp đồng thành công',
            'contract' => new ContractResource($contract->load(['customer', 'responsiblePerson', 'products']))
        ]);
    }

    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $contract = Contract::where('id', $id)
            ->when(!$user->can('contracts_all'), function ($query) use ($user) {
                return $query->where('user_id', $user->id);
            })->first();
        if (empty($contract)) {
            return response()->json(['message' => 'Không tìm thấy thông tin hợp đồng'], 404);
        }
        if ($contract->deleted_at) {
            return response()->json([
                'error' => 'Không tìm thấy thông tin hợp đồng',
            ], 404);
        }
        $data = $request->validate([
            'name' => 'required|string',
            'customer_id' => 'required|exists:customers,id',
            'responsible_person_id' => 'required|exists:users,id',
            'warranty_end_date' => 'nullable|date',
            'invoice_date' => 'nullable|date',
            'invoice_date_2' => 'nullable|date',
            'total_amount' => 'required|numeric',
            'first_payment' => 'nullable|numeric',
            'second_payment' => 'nullable|numeric',
            'notes' => 'nullable|string',
            'products' => 'required|array',
            'products.*.product' => 'required',
            'products.*.price' => 'required|numeric',
            'products.*.quantity' => 'required|integer|min:1',
            'products.*.discount' => 'nullable|numeric',
            'products.*.tax' => 'nullable',
            'products.*.total' => 'required|numeric',
            'products.*.note' => 'nullable',
        ], [
            // Thông báo lỗi cho các trường chính
            'name.required' => 'Tên hợp đồng là bắt buộc.',
            'name.string' => 'Tên hợp đồng phải là chuỗi ký tự.',
            'customer_id.required' => 'Khách hàng là bắt buộc.',
            'customer_id.exists' => 'Khách hàng không tồn tại.',
            'responsible_person_id.required' => 'Người chịu trách nhiệm là bắt buộc.',
            'responsible_person_id.exists' => 'Người chịu trách nhiệm không tồn tại.',
            'warranty_end_date.date' => 'Ngày kết thúc bảo hành phải là định dạng ngày hợp lệ.',
            'invoice_date.date' => 'Thời gian phát hành hóa đơn phải là định dạng ngày hợp lệ.',
            'total_amount.required' => 'Số tiền hợp đồng là bắt buộc.',
            'total_amount.numeric' => 'Số tiền hợp đồng phải là số.',
            'first_payment.numeric' => 'Số tiền thu đợt 1 phải là số.',
            'second_payment.numeric' => 'Số tiền thu đợt 2 phải là số.',
            'notes.string' => 'Ghi chú phải là chuỗi ký tự.',
            'products.required' => 'Danh sách sản phẩm là bắt buộc.',
            'products.array' => 'Danh sách sản phẩm phải là một mảng.',
            // Thông báo lỗi cho các trường trong mảng products
            'products.*.product.required' => 'Tên sản phẩm là bắt buộc.',
            'products.*.price.required' => 'Giá sản phẩm là bắt buộc.',
            'products.*.price.numeric' => 'Giá sản phẩm phải là số.',
            'products.*.quantity.required' => 'Số lượng sản phẩm là bắt buộc.',
            'products.*.quantity.integer' => 'Số lượng sản phẩm phải là số nguyên.',
            'products.*.quantity.min' => 'Số lượng sản phẩm phải lớn hơn hoặc bằng 1.',
            'products.*.discount.numeric' => 'Giảm giá phải là số.',
            'products.*.total.required' => 'Thành tiền là bắt buộc.',
            'products.*.total.numeric' => 'Thành tiền phải là số.',
        ]);
        $contract->update($data);
        // Xóa sản phẩm cũ và thêm mới
        $contract->products()->delete();
        $contract->products()->createMany($data['products']);
        $this->logActivity('update', Contract::class, $contract);
        return response()->json([
            'message' => 'Cập nhật hợp đồng thành công',
            'contract' => new ContractResource($contract->load(['customer', 'responsiblePerson', 'products']))
        ]);
    }

    public function destroy($id)
    {
        $user = auth()->user();

        $contract = Contract::where('id', $id)
            ->when(!$user->can('contracts_all'), function ($query) use ($user) {
                return $query->where('user_id', $user->id);
            })->first();
        if (empty($contract)) {
            return response()->json(['message' => 'Không tìm thấy thông tin hợp đồng'], 404);
        }
        if ($contract->deleted_at) {
            return response()->json([
                'error' => 'Không tìm thấy thông tin hợp đồng',
            ], 404);
        }
        $contract->update([
            'deleted_at' => now()
        ]);
        $this->logActivity('delete', Contract::class, $contract);
        return response()->json(['message' => 'Xóa hợp đồng thành công']);
    }

    public function show($id)
    {
        $user = auth()->user();
        $contract = Contract::where('id', $id)
            ->with(['customer', 'responsiblePerson', 'products'])
            ->when(!$user->can('contracts_all'), function ($query) use ($user) {
                return $query->where('user_id', $user->id);
            })->first();
        if (empty($contract)) {
            return response()->json(['message' => 'Không tìm thấy thông tin hợp đồng'], 404);
        }
        return response()->json(['contract' => new ContractResource($contract)]);
    }
}
