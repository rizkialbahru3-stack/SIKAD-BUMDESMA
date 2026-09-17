<?php

namespace App\Http\Controllers;

use App\Exports\ReportExport;
use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\Payroll;
use App\Models\Punishment;
use App\Models\Reward;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $filters = $this->filters($request);
        $data = $this->reportData($filters);

        return view('reports.index', $data + ['filters' => $filters]);
    }

    public function attendance(Request $request)
    {
        return $this->index($request->merge(['type' => 'attendance']));
    }

    public function leave(Request $request)
    {
        return $this->index($request->merge(['type' => 'leaves']));
    }

    public function payroll(Request $request)
    {
        return $this->index($request->merge(['type' => 'payroll']));
    }

    public function rewardPunishment(Request $request)
    {
        return $this->index($request->merge(['type' => 'reward-punishment']));
    }

    public function employees(Request $request)
    {
        return $this->index($request->merge(['type' => 'employees']));
    }

    public function print(Request $request)
    {
        $filters = $this->filters($request);

        return view('reports.print', $this->reportData($filters) + ['filters' => $filters]);
    }

    public function exportPdf(Request $request)
    {
        $filters = $this->filters($request);
        $pdf = Pdf::loadView('reports.print', $this->reportData($filters) + ['filters' => $filters])->setPaper('a4', 'landscape');

        return $pdf->download('laporan-'.$filters['type'].'-'.$filters['year'].'-'.$filters['month'].'.pdf');
    }

    public function exportExcel(Request $request)
    {
        $filters = $this->filters($request);
        $data = $this->reportData($filters);

        return Excel::download(new ReportExport($data['exportRows'], $data['exportHeadings']), 'laporan-'.$filters['type'].'-'.$filters['year'].'-'.$filters['month'].'.xlsx');
    }

    private function filters(Request $request): array
    {
        $data = $request->validate([
            'type' => ['nullable', 'in:all,attendance,leaves,payroll,reward-punishment,employees'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'month' => ['nullable', 'integer', 'between:1,12'],
            'year' => ['nullable', 'integer', 'min:2020', 'max:2100'],
            'status' => ['nullable', 'string', 'max:30'],
        ]);
        $data['type'] = $data['type'] ?? 'all';
        $data['month'] = (int) ($data['month'] ?? now()->month);
        $data['year'] = (int) ($data['year'] ?? now()->year);
        $data['start'] = Carbon::create($data['year'], $data['month'], 1)->startOfMonth();
        $data['end'] = $data['start']->copy()->endOfMonth();

        return $data;
    }

    private function reportData(array $filters): array
    {
        $employeeQuery = Employee::withTrashed()->with('user', 'position')->when($filters['employee_id'] ?? null, fn ($query, $id) => $query->whereKey($id));
        if (in_array($filters['status'] ?? null, ['active', 'inactive'], true)) {
            $employeeQuery->where('is_active', $filters['status'] === 'active');
        }
        $employees = $employeeQuery->orderBy('employee_code')->get();
        $employeeIds = $employees->pluck('id');
        $attendances = Attendance::with('employee.user', 'employee.position')->whereIn('employee_id', $employeeIds)->whereBetween('attendance_date', [$filters['start'], $filters['end']])->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))->get();
        $leaves = LeaveRequest::with('employee.user')->whereIn('employee_id', $employeeIds)->where(function ($query) use ($filters) {
            $query->whereBetween('start_date', [$filters['start'], $filters['end']])->orWhereBetween('end_date', [$filters['start'], $filters['end']]);
        })->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))->latest('start_date')->get();
        $payrolls = Payroll::with('employee.user')->whereIn('employee_id', $employeeIds)->whereDate('period_start', $filters['start'])->whereDate('period_end', $filters['end'])->when($filters['status'] ?? null, fn ($query, $status) => $query->where('status', $status))->get();
        $rewards = Reward::with('employee.user')->whereIn('employee_id', $employeeIds)->whereBetween('awarded_at', [$filters['start'], $filters['end']])->get();
        $punishments = Punishment::with('employee.user')->whereIn('employee_id', $employeeIds)->whereBetween('issued_at', [$filters['start'], $filters['end']])->get();
        $attendanceSummary = $this->attendanceSummary($employees, $attendances, $filters);
        $payrollSummary = ['total' => $payrolls->sum('basic_salary'), 'reward' => $payrolls->sum('reward_total'), 'deduction' => $payrolls->sum(fn ($item) => $item->late_deduction + $item->absence_deduction + $item->punishment_total), 'net' => $payrolls->sum('net_salary')];
        $export = $this->exportRows($filters, $attendanceSummary, $leaves, $payrolls, $rewards, $punishments, $employees);

        return compact('employees', 'attendances', 'leaves', 'payrolls', 'rewards', 'punishments', 'attendanceSummary', 'payrollSummary') + ['exportRows' => $export['rows'], 'exportHeadings' => $export['headings']];
    }

    private function attendanceSummary($employees, $attendances, array $filters)
    {
        $workdays = $this->workdays($filters['start'], $filters['end']);

        return $employees->map(function ($employee) use ($attendances, $workdays) {
            $items = $attendances->where('employee_id', $employee->id);
            $present = $items->whereIn('status', ['present', 'late'])->count();

            return ['employee' => $employee, 'present' => $present, 'late' => $items->where('status', 'late')->count(), 'permission' => $items->where('status', 'permission')->count(), 'leave' => $items->where('status', 'leave')->count(), 'total' => $workdays, 'percentage' => $workdays ? round($present / $workdays * 100, 1) : 0];
        });
    }

    private function workdays(Carbon $start, Carbon $end): int
    {
        $holidayDates = \App\Models\Holiday::whereBetween('holiday_date', [$start, $end])->pluck('holiday_date')->map(fn ($date) => Carbon::parse($date)->toDateString())->all();
        $days = 0;
        foreach (CarbonPeriod::create($start, $end) as $date) {
            if ($date->isWeekday() && ! in_array($date->toDateString(), $holidayDates, true)) {
                $days++;
            }
        }

        return $days;
    }

    private function exportRows(array $filters, $summary, $leaves, $payrolls, $rewards, $punishments, $employees): array
    {
        $type = $filters['type'];
        if ($type === 'attendance') {
            return ['headings' => ['ID Karyawan', 'Nama', 'Jabatan', 'Hadir', 'Terlambat', 'Izin', 'Cuti', 'Total Hari', 'Persentase'], 'rows' => $summary->map(fn ($item) => [$item['employee']->employee_code, $item['employee']->display_name, $item['employee']->position?->name, $item['present'], $item['late'], $item['permission'], $item['leave'], $item['total'], $item['percentage'].'%'])->all()];
        }
        if ($type === 'leaves') {
            return ['headings' => ['ID Karyawan', 'Nama', 'Jenis', 'Mulai', 'Selesai', 'Durasi', 'Status'], 'rows' => $leaves->map(fn ($item) => [$item->employee?->employee_code, $item->employee?->user?->name, $item->type, $item->start_date->format('d/m/Y'), $item->end_date->format('d/m/Y'), $item->total_days, $item->status])->all()];
        }
        if ($type === 'payroll') {
            return ['headings' => ['ID Karyawan', 'Nama', 'Periode', 'Gaji Pokok', 'Reward', 'Potongan', 'Gaji Bersih', 'Status'], 'rows' => $payrolls->map(fn ($item) => [$item->employee?->employee_code, $item->employee?->user?->name, $item->period_start->format('m/Y'), $item->basic_salary, $item->reward_total, $item->late_deduction + $item->absence_deduction + $item->punishment_total, $item->net_salary, $item->status])->all()];
        }
        if ($type === 'reward-punishment') {
            return ['headings' => ['Nama', 'Jenis', 'Keterangan', 'Nilai', 'Tanggal'], 'rows' => $rewards->map(fn ($item) => [$item->employee?->user?->name, 'Reward', $item->title, $item->amount, $item->awarded_at->format('d/m/Y')])->concat($punishments->map(fn ($item) => [$item->employee?->user?->name, 'Punishment', $item->title, $item->type === 'points_deduction' && $item->points > 0 ? $item->points.' poin' : $item->amount, $item->issued_at->format('d/m/Y')]))->all()];
        }

        return ['headings' => ['ID Karyawan', 'Nama', 'Jabatan', 'Tanggal Bergabung', 'Status'], 'rows' => $employees->map(fn ($item) => [$item->employee_code, $item->display_name, $item->position?->name, $item->joined_at?->format('d/m/Y'), $item->is_active ? 'Aktif' : 'Nonaktif'])->all()];
    }
}
