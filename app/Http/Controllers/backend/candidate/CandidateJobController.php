<?php

namespace App\Http\Controllers\backend\candidate;

use App\Http\Controllers\Controller;
use App\Http\Resources\backend\candidateJob\CandidateJobCollection;
use App\Http\Resources\backend\candidateJob\CandidateJobResource;
use App\Models\Candidate;
use App\Models\CandidateJob;
use App\Models\Job;
use App\Traits\LogsActivity;
use Illuminate\Http\Request;

class CandidateJobController extends Controller
{
    use LogsActivity;

    // Lấy danh sách ứng viên đã gán vào job order
    public function index(Request $request, $job_id)
    {
        $data = CandidateJob::with(['candidate' => function ($query) {
            $query->with('createBy');
        }])->where('job_id', $job_id)->orderBy('id', 'desc')->get();
        return response()->json(new CandidateJobCollection($data));
    }

    // Gán ứng viên vào job order
    public function store(Request $request, $job_id)
    {
        $request->validate([
            'candidate_id' => 'required|exists:candidates,id',
            'status' => 'in:cv_upload,cv_sent_out,proceed_interview,fail_interview,pass_interview,offer_letter',
        ]);
        // Kiểm tra xem ứng viên đã được gán vào job order này chưa
        $existingAssignment = CandidateJob::where('candidate_id', $request->candidate_id)
            ->where('job_id', $job_id)
            ->first();
        if ($existingAssignment) {
            return response()->json([
                'message' => 'Ứng viên này đã được gán vào job order này trước đó!',
                'error' => 'duplicate_assignment'
            ], 422);
        }
        $job = Job::where('id', $job_id)->first();
        $candidate = Candidate::where('id', $request->candidate_id)->first();
        $candidateJob = CandidateJob::create([
            'candidate_id' => $request->candidate_id,
            'job_id' => $job_id,
            'customer_id' => $job->customer_id,
            'status' => 'cv_upload',
        ]);
        $this->logActivity('create', CandidateJob::class, $candidateJob, 'Ứng viên "' . $candidate->full_name . '" đã được thêm vào job order "' . $job->job_title . '"');
        return response()->json([
            'message' => 'Ứng viên đã được gán vào job order thành công!',
            'data' => new CandidateJobResource($candidateJob->load(['candidate:id,full_name,phone,email'])),
        ], 201);
    }

    // Cập nhật trạng thái của ứng viên trong job order
    public function update(Request $request, $id)
    {
        $candidateJob = CandidateJob::with(['candidate:id,full_name', 'job:id,job_title'])->findOrFail($id);
        if (empty($candidateJob)) {
            return response()->json(['message' => 'Không tồn tại'], 404);
        }
        $request->validate([
            'status' => 'required|in:cv_upload,cv_sent_out,proceed_interview,fail_interview,pass_interview,offer_letter',
        ]);
        $candidateJob->update([
            'status' => $request->status,
        ]);
        $this->logActivity('update', CandidateJob::class, $candidateJob, 'Sửa trạng thái CV Ứng viên "' . $candidateJob->candidate->full_name . '" đã được thêm vào job order "' . $candidateJob->job->job_title . '"');
        return response()->json([
            'message' => 'Trạng thái đã được cập nhật thành công!',
            'data' => new CandidateJobResource($candidateJob->load(['candidate:id,full_name,phone,email'])),
        ]);
    }

    // Xóa gán ứng viên khỏi job order
    public function destroy($id)
    {
        $candidateJob = CandidateJob::with(['candidate:id,full_name', 'job:id,job_title'])->findOrFail($id);
        if (empty($candidateJob)) {
            return response()->json(['message' => 'Không tồn tại'], 404);
        }
        $candidateJob->delete();
        $this->logActivity('delete', CandidateJob::class, $candidateJob, 'Xóa Ứng viên "' . $candidateJob->candidate->full_name . '" đã được thêm vào job order "' . $candidateJob->job->job_title . '"');
        return response()->json([
            'message' => 'Ứng viên đã được xóa khỏi job order thành công!',
        ]);
    }
}
