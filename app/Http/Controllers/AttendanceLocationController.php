<?php

namespace App\Http\Controllers;

use App\Models\AttendanceLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceLocationController extends Controller
{
    public function index()
    {
        return view('attendance.locations', [
            'locations' => AttendanceLocation::orderBy('id')->get(),
            'active' => AttendanceLocation::active(),
            'title' => 'Lokasi Absensi',
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['is_active'] = true;

        DB::transaction(function () use ($data) {
            AttendanceLocation::query()->update(['is_active' => false]);
            AttendanceLocation::create($data);
        });

        return back()->with('success', 'Lokasi absensi berhasil ditambahkan dan diaktifkan.');
    }

    public function update(Request $request, AttendanceLocation $location)
    {
        $location->update($this->validated($request));

        return back()->with('success', 'Lokasi absensi berhasil diperbarui.');
    }

    public function activate(AttendanceLocation $location)
    {
        DB::transaction(function () use ($location) {
            AttendanceLocation::query()->update(['is_active' => false]);
            $location->update(['is_active' => true]);
        });

        return back()->with('success', $location->name.' kini menjadi lokasi absensi aktif.');
    }

    public function destroy(AttendanceLocation $location)
    {
        if (AttendanceLocation::count() <= 1) {
            return back()->with('error', 'Tidak dapat menghapus satu-satunya lokasi absensi.');
        }

        $wasActive = $location->is_active;
        $location->delete();

        if ($wasActive) {
            AttendanceLocation::orderBy('id')->first()?->update(['is_active' => true]);
        }

        return back()->with('success', 'Lokasi absensi berhasil dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'address' => ['nullable', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'radius_meters' => ['required', 'integer', 'min:10', 'max:5000'],
            'max_accuracy_meters' => ['required', 'integer', 'min:10', 'max:1000'],
            'enforce_radius' => ['nullable', 'boolean'],
            'enforce_checkout_radius' => ['nullable', 'boolean'],
        ]) + ['enforce_radius' => $request->boolean('enforce_radius'), 'enforce_checkout_radius' => $request->boolean('enforce_checkout_radius')];
    }
}
