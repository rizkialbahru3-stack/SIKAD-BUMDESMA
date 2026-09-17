<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\LeaveQuota;
use App\Models\LeaveRequest;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class LeaveRequestController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'type' => ['nullable', 'in:leave,sick'],
            'status' => ['nullable', 'in:pending,approved,rejected'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $query = LeaveRequest::with('employee.user', 'employee.position', 'reviewer')->latest();
        if (! $request->user()->isAdmin()) {
            $query->where('employee_id', $request->user()->employee?->id ?? 0);
        }
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->whereHas('employee', fn ($employeeQuery) => $employeeQuery
                ->where('employee_code', 'like', '%'.$search.'%')
                ->orWhere('name', 'like', '%'.$search.'%')
                ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', '%'.$search.'%')));
        }
        $query->when($filters['type'] ?? null, fn ($query, $type) => $query->where('type', $type));
        $query->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status));
        if (! empty($filters['date_from']) || ! empty($filters['date_to'])) {
            $from = $filters['date_from'] ?? '1970-01-01';
            $to = $filters['date_to'] ?? '2999-12-31';
            $query->whereDate('start_date', '<=', $to)->whereDate('end_date', '>=', $from);
        }

        $stats = null;
        if ($request->user()->isAdmin()) {
            $stats = [
                'total' => LeaveRequest::count(),
                'pending' => LeaveRequest::where('status', 'pending')->count(),
                'approved' => LeaveRequest::where('status', 'approved')->count(),
                'rejected' => LeaveRequest::where('status', 'rejected')->count(),
            ];
        }

        return view('leave.index', [
            'requests' => $query->paginate(10)->withQueryString(),
            'filters' => $filters,
            'stats' => $stats,
        ]);
    }

    public function show(Request $request, LeaveRequest $leaveRequest)
    {
        $leaveRequest->load('employee.user', 'employee.position', 'reviewer');

        if (! $request->user()->isAdmin()) {
            abort_if($leaveRequest->employee_id !== $request->user()->employee?->id, 403);
        }

        $history = LeaveRequest::where('employee_id', $leaveRequest->employee_id)
            ->where('id', '!=', $leaveRequest->id)
            ->latest()
            ->limit(5)
            ->get();

        return view('leave.show', [
            'leaveRequest' => $leaveRequest,
            'history' => $history,
            'quota' => $leaveRequest->type === 'leave' && $leaveRequest->employee ? LeaveQuota::usage($leaveRequest->employee) : null,
            'title' => 'Detail Pengajuan Cuti & Sakit',
        ]);
    }

    public function store(Request $request)
    {
        $employee = $request->user()->employee;
        abort_unless($employee?->is_active, 403, 'Akun karyawan belum terhubung.');

        $data = $request->validate([
            'type' => ['required', 'in:leave,sick'],
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['required', 'string', 'max:2000'],
            'attachment' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:2048'],
        ]);
        $data['employee_id'] = $employee->id;
        $data['total_days'] = CarbonPeriod::create($data['start_date'], $data['end_date'])->count();
        $data['attachment_path'] = $request->file('attachment')?->store('leave-attachments');

        // Cuti wajib diajukan minimal 1 minggu (7 hari kalender) sebelumnya.
        if ($data['type'] === 'leave' && $data['start_date'] < now()->addDays(7)->toDateString()) {
            throw ValidationException::withMessages([
                'start_date' => 'Pengajuan cuti minimal 1 minggu (7 hari) sebelum tanggal mulai cuti.',
            ]);
        }

        LeaveRequest::create($data);

        return back()->with('success', 'Pengajuan berhasil dikirim dan menunggu persetujuan.');
    }

    public function attachment(Request $request, LeaveRequest $leaveRequest)
    {
        if (! $request->user()->isAdmin()) {
            abort_if($leaveRequest->employee_id !== $request->user()->employee?->id, 403);
        }
        abort_unless($leaveRequest->attachment_path && Storage::disk('local')->exists($leaveRequest->attachment_path), 404);

        return Storage::disk('local')->download($leaveRequest->attachment_path);
    }

    public function review(Request $request, LeaveRequest $leaveRequest)
    {
        $data = $request->validate([
            'status' => ['required', 'in:approved,rejected'],
            'review_note' => ['nullable', 'string', 'max:2000'],
            'rejection_reason' => [Rule::requiredIf($request->input('status') === 'rejected'), 'nullable', 'string', 'min:10', 'max:2000'],
        ]);

        DB::transaction(function () use ($request, $leaveRequest, $data) {
            $fresh = LeaveRequest::whereKey($leaveRequest->id)->lockForUpdate()->first();
            abort_if($fresh->status !== 'pending', 422, 'Pengajuan sudah diproses sebelumnya.');

            if ($data['status'] === 'approved') {
                $employee = $fresh->employee;
                abort_unless($employee?->is_active, 422, 'Karyawan sudah tidak aktif.');

                if ($fresh->type === 'leave') {
                    $usage = LeaveQuota::usage($employee, (int) $fresh->start_date->format('Y'));
                    if ($usage['remaining'] < $fresh->total_days) {
                        abort(422, 'Kuota cuti karyawan tidak mencukupi.');
                    }
                    $overlap = LeaveRequest::where('employee_id', $employee->id)
                        ->where('type', 'leave')
                        ->where('status', 'approved')
                        ->where('id', '!=', $fresh->id)
                        ->whereDate('start_date', '<=', $fresh->end_date->toDateString())
                        ->whereDate('end_date', '>=', $fresh->start_date->toDateString())
                        ->exists();
                    if ($overlap) {
                        abort(422, 'Terdapat cuti yang sudah disetujui pada periode tersebut.');
                    }
                }
            }

            $fresh->update([
                'status' => $data['status'],
                'review_note' => $data['review_note'] ?? null,
                'rejection_reason' => $data['status'] === 'rejected' ? $data['rejection_reason'] : null,
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

            if ($data['status'] === 'approved') {
                foreach (CarbonPeriod::create($fresh->start_date, $fresh->end_date) as $date) {
                    Attendance::updateOrCreate(
                        ['employee_id' => $fresh->employee_id, 'attendance_date' => $date->toDateString()],
                        ['status' => $fresh->type === 'sick' ? 'sick' : ($fresh->type === 'leave' ? 'leave' : 'permission'), 'note' => $fresh->reason]
                    );
                }
            }
        });

        return back()->with('success', $data['status'] === 'approved' ? 'Pengajuan disetujui.' : 'Pengajuan ditolak.');
    }
}
