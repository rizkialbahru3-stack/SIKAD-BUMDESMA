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

        // Lokasi bersifat per karyawan: admin hanya melihatnya saat filter satu karyawan dipilih.
        $showLocation = ! $request->user()->isAdmin() || ! empty($filters['employee_id']);

        // Titik peta sebaran: seluruh hasil filter yang berkoordinat (maks 200 titik).
        $mapPoints = $showLocation ? (clone $query)->whereNotNull('latitude')->whereNotNull('longitude')
            ->reorder()->latest('visit_date')->latest('id')->limit(200)->get()
            ->map(fn (VisitAttendance $item) => [
                'lat' => (float) $item->latitude,
                'lng' => (float) $item->longitude,
                'title' => $item->title,
                'employee' => $item->employee?->user?->name ?? '-',
                'date' => $item->visit_date->translatedFormat('d M Y'),
                'url' => route('visits.show', $item),
            ])->values() : collect();

        // Rekap: hitungan kunjungan hari ini + total bulan berjalan.
        $todayCount = 0;
        $monthCount = 0;
        if ($employee) {
            $todayCount = VisitAttendance::where('employee_id', $employee->id)
                ->whereDate('visit_date', today())
                ->count();
            $monthCount = VisitAttendance::where('employee_id', $employee->id)
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
                    return [
                        'employee' => $item,
                        'today' => VisitAttendance::where('employee_id', $item->id)
                            ->whereDate('visit_date', today())
                            ->count(),
                        'month' => VisitAttendance::where('employee_id', $item->id)
                            ->whereBetween('visit_date', [$start->toDateString(), $end->toDateString()])
                            ->count(),
                    ];
                });
        }

        return view('visits.index', [
            'title' => 'Absen Kunjungan',
            'visits' => $visits,
            'filters' => $filters + ['month' => $monthValue],
            'monthNav' => $monthNav,
            'todayCount' => $todayCount,
            'monthCount' => $monthCount,
            'maxPerDay' => VisitAttendance::MAX_PER_DAY,
            'showLocation' => $showLocation,
            'mapPoints' => $mapPoints,
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
            'description' => ['nullable', 'string', 'max:2000'],
            'visit_date' => ['required', 'date', 'before_or_equal:today'],
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'accuracy' => ['nullable', 'numeric', 'min:0', 'max:100000'],
        ]);

        $visitDay = Carbon::parse($data['visit_date']);
        $count = VisitAttendance::where('employee_id', $employee->id)
            ->whereDate('visit_date', $visitDay->toDateString())
            ->count();

        if ($count >= VisitAttendance::MAX_PER_DAY) {
            return back()
                ->withInput()
                ->with('error', 'Batas maksimal '.VisitAttendance::MAX_PER_DAY.' kunjungan per hari sudah tercapai ('.$visitDay->translatedFormat('d F Y').').');
        }

        $photo = $request->file('photo')->store('visit-attendances/'.now()->format('Y/m'), 'public');

        VisitAttendance::create([
            'employee_id' => $employee->id,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'visit_date' => $data['visit_date'],
            'photo' => $photo,
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'accuracy' => isset($data['accuracy']) ? (int) round($data['accuracy']) : null,
        ]);

        return back()->with('success', 'Absen kunjungan berhasil dicatat.');
    }

    public function show(Request $request, VisitAttendance $visit)
    {
        if (! $request->user()->isAdmin()) {
            abort_if($visit->employee_id !== $request->user()->employee?->id, 403, 'Anda tidak berhak melihat kunjungan ini.');
        }

        return view('visits.show', [
            'title' => 'Detail Kunjungan',
            'visit' => $visit->load('employee.user', 'employee.position'),
        ]);
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
