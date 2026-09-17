<?php

namespace App\Http\Controllers;

use App\Models\Holiday;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    public function index(Request $request)
    {
        $data = $request->validate([
            'year' => ['nullable', 'integer', 'min:2020', 'max:2100'],
        ]);
        $year = (int) ($data['year'] ?? now()->year);
        $holidays = Holiday::whereYear('holiday_date', $year)->orderBy('holiday_date')->get();

        return view('holidays.index', [
            'holidays' => $holidays,
            'year' => $year,
            'title' => 'Hari Libur',
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'holiday_date' => ['required', 'date', 'unique:holidays,holiday_date'],
            'name' => ['required', 'string', 'max:100'],
        ]);

        Holiday::create($request->only('holiday_date', 'name'));

        return back()->with('success', 'Hari libur berhasil ditambahkan dan otomatis dikecualikan dari hitungan hari kerja.');
    }

    public function update(Request $request, Holiday $holiday)
    {
        $request->validate([
            'holiday_date' => ['required', 'date', 'unique:holidays,holiday_date,'.$holiday->id],
            'name' => ['required', 'string', 'max:100'],
        ]);

        $holiday->update($request->only('holiday_date', 'name'));

        return back()->with('success', 'Hari libur berhasil diperbarui.');
    }

    public function destroy(Holiday $holiday)
    {
        $holiday->delete();

        return back()->with('success', 'Hari libur berhasil dihapus.');
    }
}
