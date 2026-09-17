<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSetting;
use App\Models\Employee;
use App\Models\Punishment;
use App\Models\Reward;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RecognitionController extends Controller
{
    public function index(Request $request)
    {
        $employeeId = $request->user()->isAdmin() ? $request->integer('employee_id') : $request->user()->employee?->id;
        $employees = $request->user()->isAdmin() ? Employee::with('user')->where('is_active', true)->orderBy('employee_code')->get() : collect();

        return view('recognition.index', [
            'rewards' => Reward::with('employee.user')->when($employeeId, fn ($query) => $query->where('employee_id', $employeeId))->latest('awarded_at')->paginate(10, ['*'], 'rewards_page'),
            'punishments' => Punishment::with('employee.user')->when($employeeId, fn ($query) => $query->where('employee_id', $employeeId))->latest('issued_at')->paginate(10, ['*'], 'punishments_page'),
            'employees' => $employees,
            'selectedEmployee' => $employeeId,
            'pointSettings' => AttendanceSetting::current(),
        ]);
    }

    /**
     * Generate poin punishment otomatis per bulan dari total menit terlambat.
     * Idempoten: baris otomatis periode yang sama dihapus dulu lalu dibuat ulang.
     * Input manual tidak tersentuh dan potongan gaji tidak berubah (nominal = 0).
     */
    public function generateAutoPoints(Request $request)
    {
        $data = $request->validate([
            'month' => ['nullable', 'date_format:Y-m'],
        ]);
        $settings = AttendanceSetting::current();
        if (! $settings->auto_late_points_enabled) {
            return back()->with('error', 'Generate poin otomatis sedang nonaktif. Aktifkan dulu di Pengaturan Absensi.');
        }
        $block = (int) $settings->late_points_block_minutes;
        $perBlock = (int) $settings->late_points_per_block;
        if ($block < 1) {
            return back()->with('error', 'Blok menit aturan poin tidak valid. Periksa Pengaturan Absensi.');
        }

        $start = Carbon::createFromFormat('Y-m', $data['month'] ?? now()->format('Y-m'))->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $label = $start->translatedFormat('F Y');
        $generated = 0;

        DB::transaction(function () use ($start, $end, $label, $block, $perBlock, &$generated) {
            Punishment::where('is_auto', true)->whereBetween('issued_at', [$start, $end])->delete();

            foreach (Employee::where('is_active', true)->get() as $employee) {
                $lateDays = $employee->attendances()
                    ->whereBetween('attendance_date', [$start, $end])
                    ->where('status', 'late')
                    ->get();
                $totalLate = (int) $lateDays->sum('late_minutes');
                if ($totalLate <= 0) {
                    continue;
                }
                $points = (int) ceil($totalLate / $block) * $perBlock;
                if ($points <= 0) {
                    continue;
                }
                Punishment::create([
                    'employee_id' => $employee->id,
                    'type' => 'points_deduction',
                    'amount' => 0,
                    'points' => $points,
                    'is_auto' => true,
                    'title' => 'Poin keterlambatan '.$label,
                    'description' => 'Otomatis: total '.$totalLate.' menit terlambat dari '.$lateDays->count().' hari kehadiran pada '.$label.' ('.$block.' menit = '.$perBlock.' poin).',
                    'issued_at' => $end->toDateString(),
                ]);
                $generated++;
            }
        });

        return back()->with('success', 'Poin otomatis periode '.$label.' digenerate untuk '.$generated.' karyawan.');
    }

    public function storeReward(Request $request)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'type' => ['required', 'in:bonus,points'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'points' => ['nullable', 'integer', 'min:0'],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'awarded_at' => ['required', 'date'],
        ]);
        // Kolom amount/points NOT NULL: nol-kan field yang tidak relevan dengan tipe.
        if ($data['type'] === 'points') {
            $data['amount'] = 0;
            $data['points'] ??= 0;
        } else {
            $data['points'] = 0;
            $data['amount'] ??= 0;
        }
        Reward::create($data);

        return back()->with('success', 'Reward berhasil ditambahkan.');
    }

    public function storePunishment(Request $request)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'exists:employees,id'],
            'type' => ['required', 'in:warning,points_deduction,salary_deduction,administrative'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'title' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'issued_at' => ['required', 'date'],
        ]);
        // Kolom amount NOT NULL: punishment tanpa nominal (mis. peringatan) disimpan 0.
        $data['amount'] ??= 0;
        Punishment::create($data);

        return back()->with('success', 'Punishment berhasil ditambahkan.');
    }

    public function destroyReward(Request $request, Reward $reward)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $reward->delete();

        return back()->with('success', 'Reward berhasil dihapus.');
    }

    public function destroyPunishment(Request $request, Punishment $punishment)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $punishment->delete();

        return back()->with('success', 'Punishment berhasil dihapus.');
    }
}
