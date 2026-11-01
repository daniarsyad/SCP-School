<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\LeaveRequest;
use App\Models\User;
use App\Services\AttendanceLeaveSynchronizer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class LeaveRequestController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user();

        if (!$user->isSiswa() && !$user->isOrangTua()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Siswa atau Orang Tua yang dapat mengajukan izin.'
            ], 403);
        }

        $rules = [
            'start_date' => 'required|date|after_or_equal:yesterday',
            'end_date'   => 'required|date|after_or_equal:start_date',
            'reason'     => 'required|string|max:500',
            'leave_type' => 'sometimes|in:Izin,Sakit',
            'document'   => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ];

        if ($user->isOrangTua()) {
            $rules['student_id'] = 'required|exists:users,id';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        $studentId = $user->isSiswa()
            ? $user->id
            : $request->student_id;

        if ($user->isOrangTua()) {
            $isChild = User::where('id', $studentId)
                ->where('parent_id', $user->id)
                ->exists();

            if (!$isChild) {
                return response()->json([
                    'success' => false,
                    'message' => 'Siswa bukan anak dari akun ini.'
                ], 403);
            }
        }

       $documentPath = null;
    if ($request->hasFile('document')) {
    $documentPath = $request->file('document')->store('leave_documents', 'public');
    } else {
    $documentPath = ''; // biar tidak null
    }

$leave = LeaveRequest::create([
    'student_id'      => $studentId,
    'applicant_id'    => $user->id,
    'start_date'      => $request->start_date,
    'end_date'        => $request->end_date,
    'reason'          => $request->reason,
    'leave_type'      => $request->input('leave_type', 'Izin'),
    'document_path'   => $documentPath,
    'status_approval' => 'Pending',
]);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan izin berhasil dikirim',
            'data'    => $leave
        ], 201);
    }

    public function myRequests(Request $request)
    {
        $user = $request->user();

        $query = LeaveRequest::with(['student:id,fullname', 'applicant:id,fullname']);

        if ($user->isSiswa()) {
            $query->where('student_id', $user->id);
        } elseif ($user->isOrangTua()) {
            $childrenIds = User::where('parent_id', $user->id)->pluck('id');
            $query->whereIn('student_id', $childrenIds);
        } else {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak'
            ], 403);
        }

        $data = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function verify(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->isWaliKelas()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Wali Kelas yang dapat memverifikasi pengajuan.',
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'status_approval' => 'required|in:Approved,Rejected',
            'rejection_reason' => 'required_if:status_approval,Rejected|nullable|string|min:3|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        $leave = LeaveRequest::with('student:id,class_id')->findOrFail($id);

        if (
            $user->isWaliKelas() &&
            (
                !$user->class_id ||
                (int) $leave->student?->class_id !== (int) $user->class_id
            )
        ) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak berhak memproses izin dari kelas ini.'
            ], 403);
        }

        if ($leave->status_approval !== 'Pending' || $leave->cancelled_at !== null) {
            return response()->json([
                'success' => false,
                'message' => 'Pengajuan ini sudah diproses sebelumnya.'
            ], 409);
        }

        $leave = DB::transaction(function () use ($id, $request, $user, $leave) {
            $lockedLeave = LeaveRequest::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($lockedLeave->status_approval !== 'Pending' || $lockedLeave->cancelled_at !== null) {
                return null;
            }

            $lockedLeave->update([
                'status_approval' => $request->status_approval,
                'verified_by' => $user->id,
                'rejection_reason' => $request->status_approval === 'Rejected'
                    ? $request->rejection_reason
                    : null,
            ]);

            if ($request->status_approval === 'Approved') {
                $this->recalculateLeaveDates($lockedLeave, app(AttendanceLeaveSynchronizer::class));
            }

            return $lockedLeave->fresh();
        });

        if (!$leave) {
            return response()->json([
                'success' => false,
                'message' => 'Pengajuan ini sudah diproses sebelumnya.',
            ], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'Status pengajuan berhasil diubah menjadi ' . $request->status_approval,
            'data'    => $leave
        ]);
    }

    public function pending(Request $request)
    {
        $user = $request->user();

        if (!$user->isWaliKelas() && !$user->isGuruBK()) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak'
            ], 403);
        }

        $query = LeaveRequest::with(['student:id,fullname,class_id', 'applicant:id,fullname'])
            ->where('status_approval', 'Pending')
            ->whereNull('cancelled_at');

        if ($user->isWaliKelas()) {
            if (!$user->class_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akun Wali Kelas belum terhubung ke kelas.'
                ], 422);
            }

            $query->whereHas('student', function ($student) use ($user) {
                $student->where('class_id', $user->class_id);
            });
        }

        $data = $query->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }

    public function cancel(Request $request, $id)
    {
        $user = $request->user();
        $leave = LeaveRequest::findOrFail($id);

        if ((int) $leave->applicant_id !== (int) $user->id &&
            (int) $leave->student_id !== (int) $user->id) {
            return response()->json(['success' => false, 'message' => 'Anda tidak berhak membatalkan pengajuan ini.'], 403);
        }

        if ($leave->cancelled_at !== null ||
            !in_array($leave->status_approval, ['Pending', 'Approved'], true)) {
            return response()->json(['success' => false, 'message' => 'Pengajuan ini tidak dapat dibatalkan.'], 409);
        }

        $cancelled = DB::transaction(function () use ($id, $user) {
            $lockedLeave = LeaveRequest::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($lockedLeave->cancelled_at !== null ||
                !in_array($lockedLeave->status_approval, ['Pending', 'Approved'], true)) {
                return null;
            }

            $wasApproved = $lockedLeave->status_approval === 'Approved';
            $lockedLeave->update([
                'cancelled_at' => now(),
                'cancelled_by' => $user->id,
            ]);

            if ($wasApproved) {
                $this->recalculateLeaveDates($lockedLeave, app(AttendanceLeaveSynchronizer::class));
            }

            return $lockedLeave->fresh();
        });

        if (!$cancelled) {
            return response()->json(['success' => false, 'message' => 'Pengajuan ini tidak dapat dibatalkan.'], 409);
        }

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan berhasil dibatalkan.',
            'data' => $cancelled,
        ]);
    }

    private function recalculateLeaveDates(
        LeaveRequest $leave,
        AttendanceLeaveSynchronizer $synchronizer
    ): void {
        $attendances = Attendance::where('student_id', $leave->student_id)
            ->whereDate('date', '>=', $leave->start_date->toDateString())
            ->whereDate('date', '<=', $leave->end_date->toDateString())
            ->orderBy('date')
            ->get();

        foreach ($attendances as $attendance) {
            $synchronizer->recalculateForStudentDate(
                (int) $leave->student_id,
                $attendance->date->toDateString()
            );
        }
    }
}