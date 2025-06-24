<?php

namespace App\Http\Controllers\backend\logs;

use App\Http\Controllers\Controller;
use App\Http\Resources\backend\log\ActivityLogCollection;
use App\Models\ActivityLog;
use Illuminate\Http\Request;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $perPage = env('PER_PAGE', 20);
        $keyword = $request->input('keyword');
        $date_start = $request->input('date_start');
        $date_end = $request->input('date_end');
        $user_id = $request->input('user_id');
        $data = ActivityLog::orderBy('id', 'desc')->with('user:id,name')
            ->when(!$user->can('activity_logs_all'), function ($query) use ($user) {
                return $query->where('user_id', $user->id);
            })
            ->when(
                $keyword,
                fn($query) => $query->where('action', 'like', "%{$keyword}%")
            )->when(
                !empty($user_id),
                fn($query) =>
                $query->where('user_id', $user_id)
            )->when(
                !empty($date_start) && empty($date_end),
                fn($query) =>
                $query->whereBetween('created_at', ["{$date_start} 00:00:00", "{$date_start} 23:59:59"])
            )
            ->when(
                !empty($date_end) && empty($date_start),
                fn($query) =>
                $query->whereBetween('created_at', ["{$date_end} 00:00:00", "{$date_end} 23:59:59"])
            )

            ->when(
                !empty($date_start) && !empty($date_end),
                fn($query) => ($date_start === $date_end)
                    ? $query->where('created_at', '>=', "{$date_start} 00:00:00")
                    : $query->whereBetween('created_at', ["{$date_start} 00:00:00", "{$date_end} 23:59:59"])
            )->paginate($perPage);
        return response()->json(new ActivityLogCollection($data));
    }
}
