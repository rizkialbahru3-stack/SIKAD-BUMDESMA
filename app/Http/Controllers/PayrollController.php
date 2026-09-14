<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\Payroll;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    public function index(Request $request)
    {
        $period = $this->period($request->input('period'));
        $employeeId = $request->user()->isAdmin() ? $request->integer('employee_id') : $request->user()->employee?->id;
        $payrolls = Payroll::with('employee.user', 'employee.position')
            ->whereDate('period_start', $period['start'])
            ->whereDate('period_end', $period['end'])
            ->when($employeeId, fn ($query) => $query->where('employee_id', $employeeId))
            ->latest()
            ->paginate(15);

        return view('payroll.index', [
            'payrolls' => $payrolls,
            'employees' => $request->user()->isAdmin() ? Employee::with('user')->where('is_active', true)->orderBy('employee_code')->get() : collect(),
            'period' => $period,
            'selectedEmployee' => $employeeId,
        ]);
    }

    public function generate(Request $request)
    {
        abort_unless($request->user()->isAdmin(), 403);
        $data = $request->validate([
            'period' => ['required', 'date_format:Y-m'],
            'employee_id' => ['nullable', 'exists:employees,id'],
        ]);
        $period = $this->period($data['period']);
        $employees = Employee::with('attendances', 'rewards', 'punishments', 'salaryComponents')->where('is_active', true)->when($data['employee_id'] ?? null, fn ($query, $id) => $query->whereKey($id))->get();

        DB::transaction(function () use ($employees, $period) {
            foreach ($employees as $employee) {
                $attendance = $employee->attendances->filter(fn ($item) => $item->attendance_date->betweenIncluded($period['start'], $period['end']));
                $rewards = $employee->rewards->filter(fn ($item) => $item->awarded_at->betweenIncluded($period['start'], $period['end']));
                $punishments = $employee->punishments->filter(fn ($item) => $item->issued_at->betweenIncluded($period['start'], $period['end']));
                $lateRate = (float) ($employee->salaryComponents->firstWhere('type', 'late_deduction')?->amount ?? 1000);
                $absenceRate = (float) ($employee->salaryComponents->firstWhere('type', 'absence_deduction')?->amount ?? 0);
                $lateDeduction = $attendance->sum('late_minutes') * $lateRate;
                $absenceDays = max(0, $this->workdays($period['start'], $period['end']) - $attendance->whereIn('status', ['present', 'late', 'leave', 'permission', 'sick'])->count());
                $absenceDeduction = $absenceDays * $absenceRate;
                $rewardTotal = $rewards->sum('amount');
                $punishmentTotal = $punishments->sum('amount');
                $basicSalary = (float) $employee->basic_salary;
                $allowance = (float) $employee->attendance_allowance;
                $netSalary = $basicSalary + $allowance + $rewardTotal - $lateDeduction - $absenceDeduction - $punishmentTotal;

                // Kolom period_* bertipe DATE: kunci lookup harus tanggal saja (tanpa jam),
                // kalau tidak updateOrCreate tidak pernah cocok dan selalu menabrak unique.
                Payroll::updateOrCreate(['employee_id' => $employee->id, 'period_start' => $period['start']->toDateString(), 'period_end' => $period['end']->toDateString()], [
                    'basic_salary' => $basicSalary, 'attendance_allowance' => $allowance, 'reward_total' => $rewardTotal,
                    'late_deduction' => $lateDeduction, 'absence_deduction' => $absenceDeduction, 'punishment_total' => $punishmentTotal,
                    'net_salary' => max(0, $netSalary), 'status' => 'draft',
                ]);
            }
        });

        return back()->with('success', 'Penggajian periode '.$data['period'].' berhasil dibuat.');
    }

    public function updateStatus(Request $request, Payroll $payroll)
    {
        $data = $request->validate(['status' => ['required', 'in:draft,verified,paid']]);
        $payroll->update($data);

        return back()->with('success', 'Status payroll berhasil diperbarui.');
    }

    private function period(?string $value): array
    {
        $date = Carbon::createFromFormat('Y-m', $value ?: now()->format('Y-m'));

        return ['start' => $date->copy()->startOfMonth(), 'end' => $date->copy()->endOfMonth(), 'value' => $date->format('Y-m')];
    }

    private function workdays(Carbon $start, Carbon $end): int
    {
        $days = 0;
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            if ($date->isWeekday()) {
                $days++;
            }
        }

        return $days;
    }
}
