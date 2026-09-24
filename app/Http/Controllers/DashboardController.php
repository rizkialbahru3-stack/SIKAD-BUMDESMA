<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\VisitAttendance;
use App\Services\CheckoutPolicy;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $employee = auth()->user()->employee;
        $attendance = $employee?->attendances()->whereDate('attendance_date', $today)->first();
        $monthStart = $today->copy()->startOfMonth();

        return view('dashboard.index', [
            'employee' => $employee,
            'attendance' => $attendance,
            'checkout' => ($employee && $attendance?->check_in_at) ? CheckoutPolicy::checkoutEligibility($employee, $attendance) : null,
            'times' => $employee ? CheckoutPolicy::workTimes($employee, $today) : null,
            'presentCount' => $employee?->attendances()->whereBetween('attendance_date', [$monthStart, $today])->whereIn('status', ['present', 'late'])->count() ?? 0,
            'lateCount' => $employee?->attendances()->whereBetween('attendance_date', [$monthStart, $today])->where('status', 'late')->count() ?? 0,
            'leaveCount' => $employee?->leaveRequests()->whereMonth('start_date', $today->month)->where('status', 'approved')->count() ?? 0,
            'visitCount' => auth()->user()->isAdmin()
                ? VisitAttendance::whereDate('visit_date', $today->toDateString())->count()
                : ($employee?->visitAttendances()->whereDate('visit_date', $today->toDateString())->count() ?? 0),
            'adminStats' => auth()->user()->isAdmin() ? [
                'employees' => Employee::where('is_active', true)->count(),
                'present' => Attendance::whereDate('attendance_date', $today)->whereIn('status', ['present', 'late'])->count(),
                'late' => Attendance::whereDate('attendance_date', $today)->where('status', 'late')->count(),
                'pendingLeaves' => LeaveRequest::where('status', 'pending')->count(),
            ] : null,
        ]);
    }
}
