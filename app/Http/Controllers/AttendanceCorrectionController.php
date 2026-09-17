<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Services\CheckoutPolicy;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AttendanceCorrectionController extends Controller
{
    public function create()
    {
        return view('attendance.corrections.create', [
            'employees' => $this->employees(),
            'title' => 'Input Absensi Manual',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        Attendance::create($this->attributes($data));

        return redirect()->route('attendance.index')->with('success', 'Absensi manual berhasil dicatat.');
    }

    public function edit(Attendance $attendance)
    {
        return view('attendance.corrections.edit', [
            'attendance' => $attendance->load('employee.user'),
            'employees' => $this->employees(),
            'title' => 'Koreksi Absensi',
        ]);
    }

    public function update(Request $request, Attendance $attendance)
    {
        $data = $this->validated($request, $attendance);
        $attributes = $this->attributes($data);

        // Bukti foto/GPS asli tidak diubah; persetujuan pulang awal dihitung ulang
        // dari jam yang dikoreksi sehingga admin dapat menyetujui ulang bila perlu.
        $attributes['checkout_approval_type'] = null;
        $attributes['checkout_approved_by'] = null;
        $attributes['checkout_approved_at'] = null;

        $attendance->update($attributes);

        return redirect()->route('attendance.index')->with('success', 'Koreksi absensi berhasil disimpan.');
    }

    /**
     * Jam masuk/pulang + status dihitung ulang dari jam kerja karyawan
     * (jadwal personal > pengaturan umum), sama seperti absen normal.
     */
    private function attributes(array $data): array
    {
        $employee = Employee::findOrFail($data['employee_id']);
        $date = Carbon::parse($data['attendance_date'])->toDateString();
        $checkInAt = ! empty($data['check_in']) ? Carbon::parse($date.' '.$data['check_in']) : null;
        $checkOutAt = $checkInAt && ! empty($data['check_out']) ? Carbon::parse($date.' '.$data['check_out']) : null;

        if ($checkInAt) {
            $times = CheckoutPolicy::workTimes($employee, $checkInAt);
            $lateMinutes = max(0, $times['start']->diffInMinutes($checkInAt, false));

            return [
                'employee_id' => $employee->id,
                'attendance_date' => $date,
                'check_in_at' => $checkInAt,
                'check_out_at' => $checkOutAt,
                'late_minutes' => $lateMinutes,
                'work_minutes' => $checkOutAt ? $checkInAt->diffInMinutes($checkOutAt) : 0,
                'status' => $lateMinutes > 0 ? 'late' : 'present',
                'checkout_status' => $checkOutAt ? 'checked_out' : 'waiting',
                'note' => $data['note'] ?? null,
            ];
        }

        return [
            'employee_id' => $employee->id,
            'attendance_date' => $date,
            'check_in_at' => null,
            'check_out_at' => null,
            'late_minutes' => 0,
            'work_minutes' => 0,
            'status' => $data['status'],
            'checkout_status' => 'waiting',
            'note' => $data['note'] ?? null,
        ];
    }

    private function validated(Request $request, ?Attendance $attendance = null): array
    {
        $data = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'attendance_date' => ['required', 'date', 'before_or_equal:today', Rule::unique('attendances', 'attendance_date')->where(fn ($query) => $query->where('employee_id', $request->input('employee_id')))->ignore($attendance?->id)],
            'check_in' => ['nullable', 'date_format:H:i', 'required_with:check_out'],
            'check_out' => ['nullable', 'date_format:H:i', 'after:check_in'],
            'status' => ['required', 'in:present,late,leave,permission,sick,absent'],
            'note' => ['nullable', 'string', 'max:500'],
        ], [
            'attendance_date.unique' => 'Karyawan ini sudah memiliki data absensi pada tanggal tersebut — gunakan tombol koreksi (ikon pensil).',
            'check_in.required_with' => 'Jam masuk wajib diisi bila jam pulang diisi.',
        ]);

        if (empty($data['check_in']) && in_array($data['status'], ['present', 'late'], true)) {
            throw ValidationException::withMessages([
                'check_in' => 'Isi jam masuk, atau pilih status Cuti/Izin/Sakit/Alpa bila karyawan tidak hadir.',
            ]);
        }

        return $data;
    }

    private function employees()
    {
        return Employee::with('user')->orderBy('is_active', 'desc')->orderBy('employee_code')->get();
    }
}
