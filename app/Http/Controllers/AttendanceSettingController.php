<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSetting;
use Illuminate\Http\Request;

class AttendanceSettingController extends Controller
{
    public function index()
    {
        return view('attendance.settings', [
            'settings' => AttendanceSetting::current(),
            'title' => 'Pengaturan Absensi',
        ]);
    }

    public function update(Request $request)
    {
        $data = $request->validate([
            'work_start_time' => ['required', 'date_format:H:i'],
            'work_end_time' => ['required', 'date_format:H:i', 'different:work_start_time'],
            'late_tolerance_minutes' => ['required', 'integer', 'min:0', 'max:180'],
            'checkout_start_time' => ['required', 'date_format:H:i'],
            'checkout_end_time' => ['required', 'date_format:H:i', 'different:checkout_start_time'],
            'late_points_block_minutes' => ['required', 'integer', 'min:1', 'max:240'],
            'late_points_per_block' => ['required', 'integer', 'min:0', 'max:100'],
        ]) + ['auto_late_points_enabled' => $request->boolean('auto_late_points_enabled')];

        AttendanceSetting::current()->update($data);

        return back()->with('success', 'Pengaturan absensi berhasil disimpan.');
    }
}
