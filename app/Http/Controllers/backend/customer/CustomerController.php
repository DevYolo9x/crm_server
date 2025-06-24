<?php

namespace App\Http\Controllers\backend\customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\backend\customer\CustomerCollection;
use App\Http\Resources\backend\customer\CustomerResource;
use App\Models\Customer;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CustomerController extends Controller
{
    use LogsActivity;
    /**
     * Kiểm tra và tạo tên file không trùng lặp
     */
    private function getUniqueFileName($destinationPath, $originalName)
    {
        $fileName = pathinfo($originalName, PATHINFO_FILENAME);
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $newFileName = $originalName;
        $counter = 1;

        // Kiểm tra xem file đã tồn tại chưa, nếu có thì thêm hậu tố _1, _2, ...
        while (file_exists($destinationPath . '/' . $newFileName)) {
            $newFileName = $fileName . '_' . $counter . '.' . $extension;
            $counter++;
        }

        return $newFileName;
    }

    public function lists(Request $request)
    {
        $keyword = $request->input('keyword');
        $user = auth()->user();
        $data = Customer::select('id', 'name', 'code')
            ->when(!$user->can('customers_all'), function ($query) use ($user) {
                return $query->where('user_id', $user->id);
            })
            ->orderBy('name', 'asc')
            ->orderBy('id', 'desc')
            ->when($keyword, function ($query) use ($keyword) {
                $query->where('code', 'like', "%{$keyword}%")
                    ->orWhere('name', 'like', "%{$keyword}%")
                    ->orWhere('phone', 'like', "%{$keyword}%")
                    ->orWhere('email', 'like', "%{$keyword}%")
                    ->orWhere('tax_code', 'like', "%{$keyword}%")
                    ->orWhere('id', 'like', "%{$keyword}%")
                ;
            })
            ->get()
            ->map(function ($customer) {
                return [
                    'id' => $customer->id,
                    'name' => "{$customer->code} - {$customer->name}",
                ];
            });

        return response()->json(['customers' => $data]);
    }
    public function index(Request $request)
    {
        $user = auth()->user();
        $perPage = env('PER_PAGE');
        $keyword = $request->input('keyword');
        $data = Customer::with('group')->orderBy('id', 'desc')
            ->when(!$user->can('customers_all'), function ($query) use ($user) {
                return $query->where('user_id', $user->id);
            })
            ->when(
                $keyword,
                fn($query) =>
                $query->where(
                    fn($q) =>
                    $q->where('code', 'like', "%{$keyword}%")
                        ->orWhere('name', 'like', "%{$keyword}%")
                        ->orWhere('phone', 'like', "%{$keyword}%")
                        ->orWhere('email', 'like', "%{$keyword}%")
                        ->orWhere('tax_code', 'like', "%{$keyword}%")
                        ->orWhere('id', 'like', "%{$keyword}%")
                )
            )
            ->when(
                !empty($request->customer_group_id),
                fn($query) =>
                $query->where('customer_group_id', $request->customer_group_id)
            );
        $data = $data->paginate($perPage);
        return response()->json(new CustomerCollection($data));
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $data = $request->validate([
            'customer_group_id' => 'required|exists:customer_groups,id|gt:0',
            'name' => 'required',
            'tax_code' => 'required|unique:customers',
            'address' => 'required|nullable',
            'phone' => 'required|unique:customers|nullable',
            'email' => 'required|unique:customers|nullable|email',
            'attachments.*' => 'nullable|file|mimes:pdf,xls,xlsx,docx|max:10240'
        ], [
            'customer_group_id.required' => 'Nhóm khách hàng là trường bắt buộc. ',
            'customer_group_id.exists' => 'Nhóm khách hàng không tồn tại. ',
            'customer_group_id.gt' => 'Nhóm khách hàng là trường bắt buộc. ',
            'name.required' => 'Tên công ty/Tên công ty là trường bắt buộc. ',
            'tax_code.required' => 'Mã số thuế/Mã số thuế là trường bắt buộc. ',
            'tax_code.unique' => 'Mã số thuế/Mã số thuế đã tồn tại. ',
            'address.required' => 'Địa chỉ là trường bắt buộc. ',
            'phone.required' => 'Số điện thoại là trường bắt buộc. ',
            'phone.unique' => 'Số điện thoại đã tồn tại. ',
            'email.required' => 'Email là trường bắt buộc. ',
            'email.unique' => 'Email đã tồn tại. ',
            'email.email' => 'Email không đúng định dạng. ',
            'attachments.*.file' => 'File đính kèm không đúng định dạng.',

        ]);
        $lastCustomer = Customer::orderBy('code', 'desc')->first();
        if ($lastCustomer) {
            $lastCode = (int) filter_var($lastCustomer->code, FILTER_SANITIZE_NUMBER_INT); // Lấy số từ mã KHxxx
            $newCode = 'KH' . str_pad($lastCode + 1, 3, '0', STR_PAD_LEFT); // Tăng lên 1 và định dạng 3 chữ số
        } else {
            $newCode = 'KH001'; // Nếu chưa có khách hàng nào, bắt đầu từ KH001
        }
        $data['code'] = $newCode;
        $data['user_id'] = $user->id;
        $fileUrls = [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file->isValid()) {
                    $extension = $file->getClientOriginalExtension();
                    if (!in_array($extension, ['pdf', 'xls', 'xlsx', 'docx'])) {
                        return response()->json(['error' => 'Định dạng file không hợp lệ!'], 422);
                    }

                    // Lấy tên file gốc
                    $originalName = $file->getClientOriginalName();
                    $folderPath = 'uploads/customers/' . date('Y') . '/' . date('m') . '/' . date('d');
                    $destinationPath = public_path($folderPath);

                    // Kiểm tra và tạo thư mục nếu chưa tồn tại
                    if (!file_exists($destinationPath)) {
                        mkdir($destinationPath, 0755, true);
                    }

                    // Đảm bảo tên file không trùng
                    $fileName = $this->getUniqueFileName($destinationPath, $originalName);

                    // Di chuyển file vào thư mục public
                    $file->move($destinationPath, $fileName);

                    // Lưu đường dẫn
                    $fileUrls[] = "$folderPath/$fileName";
                }
            }
        }
        $data['attachment'] = json_encode($fileUrls);
        $customer = Customer::create($data);
        $this->logActivity('create', Customer::class, $customer);
        return response()->json(['message' => 'Thêm mới khách hàng thành công', 'customer' => new CustomerResource($customer)]);
    }
    public function update(Request $request, $id)
    {
        $user = auth()->user();
        $customer = Customer::where(['id' => $id])
            ->when(!$user->can('customers_all'), function ($query) use ($user) {
                return $query->where('user_id', $user->id);
            })
            ->first();
        if (empty($customer)) {
            return response()->json(['message' => 'Khách hàng không tồn tại'], 404);
        }
        $data = $request->validate([
            'customer_group_id' => 'required|exists:customer_groups,id|gt:0',
            'name' => 'required',
            'tax_code' => 'required|unique:customers,id',
            'address' => 'required|nullable',
            'phone' => 'required|unique:customers,id|nullable',
            'email' => 'required|unique:customers,id|nullable|email',
            'attachments.*' => 'nullable|file|mimes:pdf,xls,xlsx,docx|max:10240'

        ], [
            'customer_group_id.required' => 'Nhóm khách hàng là trường bắt buộc. ',
            'customer_group_id.exists' => 'Nhóm khách hàng không tồn tại. ',
            'customer_group_id.gt' => 'Nhóm khách hàng là trường bắt buộc. ',
            'name.required' => 'Tên công ty/Tên công ty là trường bắt buộc. ',
            'tax_code.required' => 'Mã số thuế/Mã số thuế là trường bắt buộc. ',
            'tax_code.unique' => 'Mã số thuế/Mã số thuế đã tồn tại. ',
            'address.required' => 'Địa chỉ là trường bắt buộc. ',
            'phone.required' => 'Số điện thoại là trường bắt buộc. ',
            'phone.unique' => 'Số điện thoại đã tồn tại. ',
            'email.required' => 'Email là trường bắt buộc. ',
            'email.unique' => 'Email đã tồn tại. ',
            'email.email' => 'Email không đúng định dạng. ',
            'attachments.*.file' => 'File đính kèm không đúng định dạng.',

        ]);
        $fileUrls = json_decode($customer->attachment, true) ?? [];
        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                if ($file->isValid()) {
                    $extension = $file->getClientOriginalExtension();
                    if (!in_array($extension, ['pdf', 'xls', 'xlsx', 'docx'])) {
                        return response()->json(['error' => 'Định dạng file không hợp lệ!'], 422);
                    }

                    // Lấy tên file gốc
                    $originalName = $file->getClientOriginalName();
                    $folderPath = 'uploads/customers/' . date('Y') . '/' . date('m') . '/' . date('d');
                    $destinationPath = public_path($folderPath);

                    // Kiểm tra và tạo thư mục nếu chưa tồn tại
                    if (!file_exists($destinationPath)) {
                        mkdir($destinationPath, 0755, true);
                    }

                    // Đảm bảo tên file không trùng
                    $fileName = $this->getUniqueFileName($destinationPath, $originalName);

                    // Di chuyển file vào thư mục public
                    $file->move($destinationPath, $fileName);

                    // Lưu đường dẫn
                    $fileUrls[] = "$folderPath/$fileName";
                }
            }
        }
        $customer->update([
            'name' => $request->name,
            'phone' => $request->phone,
            'address' => $request->address,
            'email' => $request->email,
            'tax_code' => $request->tax_code,
            'customer_group_id' => $request->customer_group_id,
            'attachment' => json_encode($fileUrls),
        ]);
        $this->logActivity('update', Customer::class, $customer);
        return response()->json(['message' => 'Cập nhập khách hàng thành công', 'customer' => new CustomerResource($customer)]);
    }
    public function destroy($id)
    {
        $user = auth()->user();
        $customer = Customer::where(['id' => $id])
            ->when(!$user->can('customers_all'), function ($query) use ($user) {
                return $query->where('user_id', $user->id);
            })
            ->first();
        if (empty($customer)) {
            return response()->json(['message' => 'Khách hàng không tồn tại'], 404);
        }

        // Xóa các file đính kèm
        $attachments = json_decode($customer->attachment, true) ?? [];
        foreach ($attachments as $filePath) {
            $fullPath = public_path($filePath);
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        }

        $this->logActivity('delete', Customer::class, $customer);
        $customer->delete();
        return response()->json(['message' => 'Deleted']);
    }
    public function removeAttachment(Request $request, $id)
    {
        $user = auth()->user();
        $customer = Customer::where(['id' => $id])
            ->when(!$user->can('customers_all'), function ($query) use ($user) {
                return $query->where('user_id', $user->id);
            })
            ->first();
        if (empty($customer)) {
            return response()->json(['message' => 'Khách hàng không tồn tại'], 404);
        }

        $data = $request->validate([
            'file_url' => 'required',
        ]);
        $filePath = $request->file_url;
        $attachments = json_decode($customer->attachment, true) ?? [];
        if (!in_array($filePath, $attachments)) {
            return response()->json(['message' => 'File không tồn tại'], 404);
        }

        // Xóa file vật lý
        $fullPath = public_path($filePath);
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }

        // Cập nhật danh sách attachment
        $updatedAttachments = array_filter($attachments, fn($path) => $path !== $filePath);
        $customer->update([
            'attachment' => json_encode(array_values($updatedAttachments)),
        ]);
        $this->logActivity('update', Customer::class, $customer);
        return response()->json(['message' => 'Xóa file thành công', 'customer' => new CustomerResource($customer)]);
    }
}
