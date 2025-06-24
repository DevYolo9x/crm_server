<?php

namespace App\Http\Controllers\backend;

use App\Http\Controllers\Controller;
use App\Models\Candidate;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Job;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function statistics(Request $request)
    {
        $user = auth()->user();
        // Thống kê tổng số
        $customerCount = Customer::when(!$user->can('dashboard_all'), function ($query) use ($user) {
            return $query->where('user_id', $user->id);
        })->count();
        $jobsCount = Job::when(!$user->can('dashboard_all'), function ($query) use ($user) {
            return $query->where('created_by', $user->id);
        })->count();
        $candidateCount = Candidate::when(!$user->can('dashboard_all'), function ($query) use ($user) {
            return $query->where('created_by', $user->id);
        })->count();
        $contractCount = Contract::when(!$user->can('dashboard_all'), function ($query) use ($user) {
            return $query->where('created_by', $user->id);
        })->whereNull('deleted_at')->count();
        // Chuẩn bị dữ liệu trả về
        return response()->json(['totals' => [
            'customers' => $customerCount,
            'jobs' => $jobsCount,
            'candidates' => $candidateCount,
            'contracts' => $contractCount,
        ],]);
    }
    // API 2: Lấy dữ liệu biểu đồ
    public function chart(Request $request)
    {
        $type = $request->input('type', 'week'); // Mặc định là tuần
        switch ($type) {
            case 'week':
                $start = Carbon::now()->startOfWeek();
                $end = Carbon::now()->endOfWeek();
                $groupByFormatSQL = '%Y-%m-%d';
                $groupByFormatPHP = 'Y-m-d';
                $periodCount = 7;
                $labels = ['Thứ 2', 'Thứ 3', 'Thứ 4', 'Thứ 5', 'Thứ 6', 'Thứ 7', 'Chủ nhật'];
                break;

            case 'month':
                $start = Carbon::now()->startOfMonth();
                $end = Carbon::now()->endOfMonth();
                $groupByFormatSQL = '%Y-%m-%d';
                $groupByFormatPHP = 'Y-m-d';
                $periodCount = $start->daysInMonth;
                $labels = array_map(fn($day) => "Ngày $day", range(1, $start->daysInMonth));
                break;

            case 'year':
            default:
                $start = Carbon::now()->startOfYear();
                $end = Carbon::now()->endOfYear();
                $groupByFormatSQL = '%Y-%m';
                $groupByFormatPHP = 'Y-m';
                $periodCount = 12;
                $labels = ['Tháng 1', 'Tháng 2', 'Tháng 3', 'Tháng 4', 'Tháng 5', 'Tháng 6', 'Tháng 7', 'Tháng 8', 'Tháng 9', 'Tháng 10', 'Tháng 11', 'Tháng 12'];
                break;
        }

        // Function xử lý thống kê
        $getStats = function ($model) use ($groupByFormatSQL, $start, $end) {
            $user = auth()->user();
            $query = $model::selectRaw("DATE_FORMAT(created_at, '{$groupByFormatSQL}') as period, COUNT(*) as count")
                ->whereBetween('created_at', [$start, $end]);

            if ($model === Contract::class) {
                $query->whereNull('deleted_at');
            }
            if ($model === Customer::class) {
                $query->when(!$user->can('dashboard_all'), function ($query) use ($user) {
                    return $query->where('user_id', $user->id);
                });
            } else {
                $query->when(!$user->can('dashboard_all'), function ($query) use ($user) {
                    return $query->where('created_by', $user->id);
                });
            }
            return $query->groupBy('period')
                ->orderBy('period', 'asc')
                ->pluck('count', 'period')
                ->all();
        };

        // Lấy dữ liệu
        $stats = [
            'customers' => $getStats(Customer::class),
            'jobs' => $getStats(Job::class),
            'candidates' => $getStats(Candidate::class),
            'contracts' => $getStats(Contract::class),
        ];

        // Khởi tạo dữ liệu chart
        $chartData = [
            'customers' => [],
            'jobs' => [],
            'candidates' => [],
            'contracts' => [],
        ];

        // Tạo dữ liệu
        for ($i = 0; $i < $periodCount; $i++) {
            $date = match ($type) {
                'week', 'month' => $start->copy()->addDays($i)->format($groupByFormatPHP),
                'year' => $start->copy()->addMonths($i)->format($groupByFormatPHP),
            };

            foreach ($chartData as $key => &$data) {
                $data[] = $stats[$key][$date] ?? 0;
            }
        }

        return response()->json([
            'chart_data' => $chartData,
            'labels' => $labels,
        ]);
    }
}
