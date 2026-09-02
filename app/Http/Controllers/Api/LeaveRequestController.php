<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;

class LeaveRequestController extends Controller
{
    // Ajukan Izin (Siswa / Orang Tua)
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'student_id'   => 'required|exists:users,id',
            'start_date'   => 'required|date|after_or_equal:today',
            'end_date'     => 'required|date|after_or_equal:start_date',
            'reason'       => 'required|string',
            'document'     => 'required|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        // Hanya Siswa atau Orang Tua yang boleh mengajukan
        if (!$user->isSiswa() && !$user->isOrangTua()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Siswa atau Orang Tua yang dapat mengajukan izin.'
            ], 403);
        }

        // Upload dokumen
        $documentPath = $request->file('document')->store('leave_documents', 'public');

        $leave = LeaveRequest::create([
            'student_id'      => $request->student_id,
            'applicant_id'    => $user->id,
            'start_date'      => $request->start_date,
            'end_date'        => $request->end_date,
            'reason'          => $request->reason,
            'document_path'   => $documentPath,
            'status_approval' => 'Pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Pengajuan izin berhasil dikirim',
            'data'    => $leave
        ], 201);
    }

    // Lihat pengajuan milik sendiri / anaknya
    public function myRequests(Request $request)
    {
        $user = $request->user();

        $query = LeaveRequest::with(['student:id,fullname', 'applicant:id,fullname']);

        if ($user->isSiswa()) {
            $query->where('student_id', $user->id);
        } elseif ($user->isOrangTua()) {
            // Ambil anak-anaknya
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

    // Verifikasi Izin (Wali Kelas / Guru BK)
    public function verify(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'status_approval' => 'required|in:Approved,Rejected',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if (!$user->isWaliKelas() && !$user->isGuruBK()) {
            return response()->json([
                'success' => false,
                'message' => 'Hanya Wali Kelas atau Guru BK yang dapat memverifikasi.'
            ], 403);
        }

        $leave = LeaveRequest::findOrFail($id);

        if ($leave->status_approval !== 'Pending') {
            return response()->json([
                'success' => false,
                'message' => 'Pengajuan ini sudah diproses sebelumnya.'
            ], 409);
        }

        $leave->update([
            'status_approval' => $request->status_approval,
            'verified_by'     => $user->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Status pengajuan berhasil diubah menjadi ' . $request->status_approval,
            'data'    => $leave
        ]);
    }

    // List semua pending (untuk Wali Kelas / BK)
    public function pending(Request $request)
    {
        $user = $request->user();

        if (!$user->isWaliKelas() && !$user->isGuruBK()) {
            return response()->json([
                'success' => false,
                'message' => 'Akses ditolak'
            ], 403);
        }

        $data = LeaveRequest::with(['student:id,fullname,class_id', 'applicant:id,fullname'])
            ->where('status_approval', 'Pending')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $data
        ]);
    }
}