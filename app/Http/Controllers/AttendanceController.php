<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\AttendanceLocation;
use App\Models\AttendanceSetting;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Services\AttendanceLocationService;
use App\Services\CheckoutPolicy;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'status' => ['nullable', 'in:present,late,leave,permission,sick,absent'],
            'checkout_status' => ['nullable', 'in:waiting,approved,rejected,checked_out'],
            'month' => ['nullable', 'date_format:Y-m'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
        ]);
        if (! empty($filters['month'])) {
            $filters['date_from'] = date('Y-m-01', strtotime($filters['month'].'-01'));
            $filters['date_to'] = date('Y-m-t', strtotime($filters['month'].'-01'));
        } elseif (empty($filters['date_from']) && empty($filters['date_to'])) {
            $filters['date_from'] = now()->copy()->startOfMonth()->toDateString();
            $filters['date_to'] = now()->copy()->endOfMonth()->toDateString();
        }
        $monthValue = substr($filters['date_from'], 0, 7);
        $monthNames = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];
        $monthLabel = fn (string $ym) => $monthNames[(int) substr($ym, 5, 2)].' '.substr($ym, 0, 4);
        $monthNav = [
            'value' => $monthValue,
            'label' => $monthLabel($monthValue),
            'prev' => date('Y-m', strtotime($monthValue.'-01 -1 month')),
            'prevLabel' => $monthLabel(date('Y-m', strtotime($monthValue.'-01 -1 month'))),
            'next' => date('Y-m', strtotime($monthValue.'-01 +1 month')),
            'nextLabel' => $monthLabel(date('Y-m', strtotime($monthValue.'-01 +1 month'))),
            'isCurrent' => $monthValue === now()->format('Y-m'),
        ];
        $query = Attendance::with('employee.user', 'employee.position')->latest('attendance_date')->latest('check_in_at');

        if (! $request->user()->isAdmin()) {
            $query->where('employee_id', $request->user()->employee?->id ?? 0);
        }
        if (! empty($filters['search'])) {
            $query->whereHas('employee', fn ($employeeQuery) => $employeeQuery->where('employee_code', 'like', '%'.$filters['search'].'%')->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', '%'.$filters['search'].'%')));
        }
        $query->when($filters['date_from'] ?? null, fn ($query, $date) => $query->whereDate('attendance_date', '>=', $date));
        $query->when($filters['date_to'] ?? null, fn ($query, $date) => $query->whereDate('attendance_date', '<=', $date));
        $query->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status));
        $query->when($filters['checkout_status'] ?? null, fn ($query, $status) => $query->where('checkout_status', $status));
        $query->when($filters['employee_id'] ?? null, fn ($query, $employeeId) => $query->where('employee_id', $employeeId));

        return view('attendance.index', [
            'attendances' => $query->paginate(15),
            'employees' => $request->user()->isAdmin() ? Employee::with('user')->where('is_active', true)->orderBy('employee_code')->get() : collect(),
            'filters' => $filters,
            'monthNav' => $monthNav,
            'settings' => AttendanceSetting::current(),
        ]);
    }

    public function show(Request $request, Attendance $attendance)
    {
        // Karyawan hanya boleh melihat absensinya sendiri; admin boleh semua.
        abort_unless(
            $request->user()->isAdmin() || $attendance->employee_id === $request->user()->employee?->id,
            403,
            'Anda tidak berhak melihat absensi ini.'
        );

        return view('attendance.show', [
            'attendance' => $attendance->load('employee.user', 'employee.position'),
            'office' => AttendanceLocation::active(),
            'title' => 'Detail Absensi',
        ]);
    }

    public function checkIn(Request $request)
    {
        abort_if($request->user()->isAdmin(), 403, 'Administrator tidak perlu melakukan absensi.');
        $employee = $request->user()->employee;
        abort_unless($employee?->is_active, 403, 'Akun karyawan belum terhubung.');

        $now = now();
        $onLeave = LeaveRequest::where('employee_id', $employee->id)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $now->toDateString())
            ->whereDate('end_date', '>=', $now->toDateString())
            ->exists();
        if ($onLeave) {
            return $this->attendanceFailed($request, 'Anda memiliki pengajuan cuti/izin yang disetujui pada hari ini.');
        }
        $attendance = Attendance::firstOrCreate(['employee_id' => $employee->id, 'attendance_date' => $now->toDateString()]);
        if ($attendance->check_in_at) {
            return $this->attendanceFailed($request, 'Absensi masuk hari ini sudah tercatat.');
        }

        $evidence = $this->validatedEvidence($request);
        if (! $evidence['ok']) {
            return $this->attendanceFailed($request, $evidence['message'], 422);
        }

        $lateMinutes = 0;
        $times = CheckoutPolicy::workTimes($employee, $now);
        $lateMinutes = max(0, $times['start']->diffInMinutes($now, false));

        $attendance->update([
            'check_in_at' => $now,
            'late_minutes' => $lateMinutes,
            'status' => $lateMinutes > 0 ? 'late' : 'present',
            'check_in_photo' => $evidence['photo'],
            'check_in_latitude' => $evidence['latitude'],
            'check_in_longitude' => $evidence['longitude'],
            'check_in_accuracy' => $evidence['accuracy'],
            'check_in_distance_meters' => $evidence['distance'],
            'check_in_location_valid' => $evidence['valid'],
        ]);

        return $this->attendanceSuccess($request, $attendance->fresh(), 'masuk', 'Absensi masuk berhasil dicatat pada '.$now->format('H:i').' WIB.');
    }

    public function checkOut(Request $request)
    {
        abort_if($request->user()->isAdmin(), 403, 'Administrator tidak perlu melakukan absensi.');
        $employee = $request->user()->employee;
        $attendance = $employee?->attendances()->whereDate('attendance_date', today())->first();
        abort_unless($attendance?->check_in_at, 422, 'Lakukan absensi masuk terlebih dahulu.');

        if ($attendance->check_out_at) {
            return $this->attendanceFailed($request, 'Absensi pulang hari ini sudah tercatat.');
        }

        $eligibility = CheckoutPolicy::checkoutEligibility($employee, $attendance);
        if (! $eligibility['allowed']) {
            return $this->attendanceFailed($request, $eligibility['reason'], 422);
        }

        $evidence = $this->validatedEvidence($request);
        if (! $evidence['ok']) {
            return $this->attendanceFailed($request, $evidence['message'], 422);
        }

        $now = now();
        $attendance->update([
            'check_out_at' => $now,
            'work_minutes' => $attendance->check_in_at->diffInMinutes($now),
            'checkout_status' => 'checked_out',
            'check_out_photo' => $evidence['photo'],
            'check_out_latitude' => $evidence['latitude'],
            'check_out_longitude' => $evidence['longitude'],
            'check_out_accuracy' => $evidence['accuracy'],
            'check_out_distance_meters' => $evidence['distance'],
            'check_out_location_valid' => $evidence['valid'],
        ]);

        return $this->attendanceSuccess($request, $attendance->fresh(), 'pulang', 'Absensi pulang berhasil dicatat pada '.$now->format('H:i').' WIB.');
    }

    /**
     * Validasi foto selfie + koordinat GPS, simpan foto ke Storage,
     * dan validasi radius di BACKEND. Waktu selalu memakai server (now()).
     */
    private function validatedEvidence(Request $request): array
    {
        $data = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ]);

        $latitude = (float) $data['latitude'];
        $longitude = (float) $data['longitude'];
        $accuracy = isset($data['accuracy']) ? (float) $data['accuracy'] : null;

        // Cek lokasi SEBELUM menyimpan foto agar tidak ada file yatim.
        // Absen pulang: catat-saja (boleh dari lapangan) kecuali admin mengaktifkan
        // penegakan radius pulang; absen masuk tetap wajib dalam radius kantor.
        $isCheckout = $request->route()->getName() === 'attendance.check-out';
        $office = AttendanceLocation::active();
        $enforce = (bool) ($office && ($isCheckout ? $office->enforce_checkout_radius : $office->enforce_radius));
        $check = AttendanceLocationService::check($latitude, $longitude, $accuracy, $enforce);
        if ($check['state'] === 'poor_accuracy' || $check['state'] === 'outside') {
            return ['ok' => false, 'message' => $check['message']];
        }

        $folder = ($request->route()->getName() === 'attendance.check-out' ? 'attendance/check-out/' : 'attendance/check-in/').now()->format('Y/m');
        $photo = $request->file('photo')->store($folder, 'public');

        return [
            'ok' => true,
            'photo' => $photo,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'accuracy' => $accuracy !== null ? (int) round($accuracy) : null,
            'distance' => $check['distance'],
            'valid' => $check['state'] === 'ok' ? true : ($check['state'] === 'unchecked' ? null : false),
            'location' => $check['location'],
        ];
    }

    private function attendanceFailed(Request $request, string $message, int $status = 422)
    {
        if ($request->wantsJson()) {
            return response()->json(['ok' => false, 'message' => $message], $status);
        }

        return back()->with('error', $message);
    }

    private function attendanceSuccess(Request $request, Attendance $attendance, string $type, string $message)
    {
        if ($request->wantsJson()) {
            $at = $type === 'pulang' ? $attendance->check_out_at : $attendance->check_in_at;

            return response()->json([
                'ok' => true,
                'message' => $message,
                'type' => $type,
                'attendance_id' => $attendance->id,
                'detail_url' => route('attendance.show', $attendance),
                'date' => $attendance->attendance_date->translatedFormat('d F Y'),
                'time' => $at?->format('H:i:s').' WIB',
                'status' => $attendance->status,
                'photo_url' => asset('storage/'.($type === 'pulang' ? $attendance->check_out_photo : $attendance->check_in_photo)),
                'latitude' => $type === 'pulang' ? $attendance->check_out_latitude : $attendance->check_in_latitude,
                'longitude' => $type === 'pulang' ? $attendance->check_out_longitude : $attendance->check_in_longitude,
                'accuracy' => $type === 'pulang' ? $attendance->check_out_accuracy : $attendance->check_in_accuracy,
                'distance' => $type === 'pulang' ? $attendance->check_out_distance_meters : $attendance->check_in_distance_meters,
            ]);
        }

        return back()->with('success', $message);
    }

    public function updateCheckoutApproval(Request $request, Attendance $attendance)
    {
        $data = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'type' => ['nullable', 'in:early,special'],
        ]);

        if ($attendance->check_out_at) {
            return back()->with('error', 'Karyawan sudah absen pulang, persetujuan tidak dapat diubah.');
        }

        if ($data['decision'] === 'approved') {
            $attendance->update([
                'checkout_status' => 'approved',
                'checkout_approval_type' => $data['type'] ?? 'early',
                'checkout_approved_by' => $request->user()->id,
                'checkout_approved_at' => now(),
            ]);

            return back()->with('success', 'Pulang lebih awal berhasil diizinkan untuk karyawan.');
        }

        $attendance->update([
            'checkout_status' => 'rejected',
            'checkout_approval_type' => null,
            'checkout_approved_by' => null,
            'checkout_approved_at' => null,
        ]);

        return back()->with('success', 'Pengajuan pulang awal berhasil ditolak.');
    }
}
