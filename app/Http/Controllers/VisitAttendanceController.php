<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\VisitAttendance;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class VisitAttendanceController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $monthValue = $filters['month'] ?? now()->format('Y-m');
        $start = Carbon::createFromFormat('Y-m', $monthValue)->startOfMonth();
        $end = Carbon::createFromFormat('Y-m', $monthValue)->endOfMonth();

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

        $query = VisitAttendance::with('employee.user', 'employee.position')
            ->whereBetween('visit_date', [$start->toDateString(), $end->toDateString()])
            ->latest('visit_date')
            ->latest('id');

        $employee = $request->user()->employee;
        if (! $request->user()->isAdmin()) {
            $query->where('employee_id', $employee?->id ?? 0);
        } elseif (! empty($filters['employee_id'])) {
            $query->where('employee_id', $filters['employee_id']);
        }

        if (! empty($filters['search'])) {
            $query->where('title', 'like', '%'.$filters['search'].'%');
        }

        $visits = $query->paginate(15)->withQueryString();

        // Rekap: karyawan lihat kuotanya sendiri; admin lihat ringkasan per karyawan.
        $myCount = 0;
        if ($employee) {
            $myCount = VisitAttendance::where('employee_id', $employee->id)
                ->whereBetween('visit_date', [$start->toDateString(), $end->toDateString()])
                ->count();
        }
        $summary = collect();
        if ($request->user()->isAdmin()) {
            $summary = Employee::with('user')
                ->where('is_active', true)
                ->orderBy('employee_code')
                ->get()
                ->map(function (Employee $item) use ($start, $end) {
                    $count = VisitAttendance::where('employee_id', $item->id)
                        ->whereBetween('visit_date', [$start->toDateString(), $end->toDateString()])
                        ->count();

                    return [
                        'employee' => $item,
                        'count' => $count,
                        'remaining' => max(0, VisitAttendance::MAX_PER_MONTH - $count),
                    ];
                });
        }

        return view('visits.index', [
            'title' => 'Absen Kunjungan',
            'visits' => $visits,
            'filters' => $filters + ['month' => $monthValue],
            'monthNav' => $monthNav,
            'myCount' => $myCount,
            'remaining' => max(0, VisitAttendance::MAX_PER_MONTH - $myCount),
            'maxPerMonth' => VisitAttendance::MAX_PER_MONTH,
            'summary' => $summary,
            'employees' => $request->user()->isAdmin() ? Employee::with('user')->where('is_active', true)->orderBy('employee_code')->get() : collect(),
        ]);
    }

    public function store(Request $request)
    {
        $employee = $request->user()->employee;
        abort_unless($employee?->is_active, 403, 'Akun karyawan belum terhubung.');

        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'visit_date' => ['required', 'date', 'before_or_equal:today'],
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ]);

        $visitMonth = Carbon::parse($data['visit_date']);
        $count = VisitAttendance::where('employee_id', $employee->id)
            ->whereYear('visit_date', $visitMonth->year)
            ->whereMonth('visit_date', $visitMonth->month)
            ->count();

        if ($count >= VisitAttendance::MAX_PER_MONTH) {
            return back()
                ->withInput()
                ->with('error', 'Batas maksimal '.VisitAttendance::MAX_PER_MONTH.' kunjungan per bulan sudah tercapai ('.$visitMonth->translatedFormat('F Y').').');
        }

        $photo = $request->file('photo')->store('visit-attendances/'.now()->format('Y/m'), 'public');

        VisitAttendance::create([
            'employee_id' => $employee->id,
            'title' => $data['title'],
            'visit_date' => $data['visit_date'],
            'photo' => $photo,
        ]);

        return back()->with('success', 'Absen kunjungan berhasil dicatat ('.($count + 1).'/'.VisitAttendance::MAX_PER_MONTH.' bulan ini).');
    }

    public function destroy(Request $request, VisitAttendance $visit)
    {
        if (! $request->user()->isAdmin()) {
            abort_if($visit->employee_id !== $request->user()->employee?->id, 403, 'Anda tidak berhak menghapus kunjungan ini.');
        }

        Storage::disk('public')->delete($visit->photo);
        $visit->delete();

        return back()->with('success', 'Data kunjungan berhasil dihapus.');
    }
}
