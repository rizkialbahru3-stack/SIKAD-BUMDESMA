@extends('layouts.app')

@section('content')
<div class="mb-4">
    <a class="text-decoration-none" href="{{ route('attendance.index') }}"><i class="bi bi-arrow-left me-2"></i>Kembali ke Data Kehadiran</a>
    <h1 class="h3 mt-3 mb-1">Pengaturan Absensi</h1>
    <p class="text-secondary mb-0">Atur jam kerja, toleransi keterlambatan, dan rentang absensi pulang.</p>
    <a class="btn btn-outline-success mt-3" href="{{ route('attendance.locations.index') }}"><i class="bi bi-geo-alt me-2"></i>Pengaturan Lokasi Absensi (titik kantor, radius &amp; akurasi GPS)</a>
</div>

<form method="POST" action="{{ route('attendance.settings.update') }}" class="row g-4">
    @csrf
    @method('PUT')
    <div class="col-lg-6 order-1">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3"><h2 class="h5 mb-0"><i class="bi bi-clock me-2 text-primary"></i>Jam Kerja</h2></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label" for="work_start_time">Jam Masuk</label>
                        <input class="form-control @error('work_start_time') is-invalid @enderror" id="work_start_time" type="time" name="work_start_time" value="{{ old('work_start_time', substr((string) $settings->work_start_time, 0, 5)) }}" required>
                        @error('work_start_time')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="work_end_time">Jam Pulang</label>
                        <input class="form-control @error('work_end_time') is-invalid @enderror" id="work_end_time" type="time" name="work_end_time" value="{{ old('work_end_time', substr((string) $settings->work_end_time, 0, 5)) }}" required>
                        @error('work_end_time')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="form-text mt-2">Karyawan dengan jadwal kerja khusus mengikuti jam pada jadwalnya masing-masing.</div>
            </div>
        </div>
    </div>
    <div class="col-lg-6 order-2">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3"><h2 class="h5 mb-0"><i class="bi bi-alarm me-2 text-primary"></i>Keterlambatan</h2></div>
            <div class="card-body">
                <label class="form-label" for="late_tolerance_minutes">Batas Toleransi (menit)</label>
                <input class="form-control @error('late_tolerance_minutes') is-invalid @enderror" id="late_tolerance_minutes" type="number" name="late_tolerance_minutes" min="0" max="180" value="{{ old('late_tolerance_minutes', $settings->late_tolerance_minutes) }}" required>
                @error('late_tolerance_minutes')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
                <div class="form-text mt-2">Absen setelah jam masuk tercatat Terlambat; jumlah menit aktual selalu tersimpan.</div>
            </div>
        </div>
    </div>
    <div class="col-lg-6 order-3">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3"><h2 class="h5 mb-0"><i class="bi bi-box-arrow-right me-2 text-primary"></i>Absensi Pulang</h2></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label" for="checkout_start_time">Mulai Absen Pulang</label>
                        <input class="form-control @error('checkout_start_time') is-invalid @enderror" id="checkout_start_time" type="time" name="checkout_start_time" value="{{ old('checkout_start_time', substr((string) $settings->checkout_start_time, 0, 5)) }}" required>
                        @error('checkout_start_time')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="checkout_end_time">Akhir Absen Pulang</label>
                        <input class="form-control @error('checkout_end_time') is-invalid @enderror" id="checkout_end_time" type="time" name="checkout_end_time" value="{{ old('checkout_end_time', substr((string) $settings->checkout_end_time, 0, 5)) }}" required>
                        @error('checkout_end_time')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-6 order-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-header bg-white py-3"><h2 class="h5 mb-0"><i class="bi bi-lightning me-2 text-primary"></i>Poin Keterlambatan Otomatis</h2></div>
            <div class="card-body">
                <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" id="auto_late_points_enabled" name="auto_late_points_enabled" value="1" @checked(old('auto_late_points_enabled', $settings->auto_late_points_enabled))><label class="form-check-label" for="auto_late_points_enabled">Aktifkan generate poin otomatis</label></div>
                <div class="row g-3">
                    <div class="col-sm-6">
                        <label class="form-label" for="late_points_block_minutes">Setiap (menit)</label>
                        <input class="form-control @error('late_points_block_minutes') is-invalid @enderror" id="late_points_block_minutes" type="number" name="late_points_block_minutes" min="1" max="240" value="{{ old('late_points_block_minutes', $settings->late_points_block_minutes) }}" required>
                        @error('late_points_block_minutes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <div class="col-sm-6">
                        <label class="form-label" for="late_points_per_block">Poin per blok</label>
                        <input class="form-control @error('late_points_per_block') is-invalid @enderror" id="late_points_per_block" type="number" name="late_points_per_block" min="0" max="100" value="{{ old('late_points_per_block', $settings->late_points_per_block) }}" required>
                        @error('late_points_per_block')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>
                <div class="form-text mt-2">Contoh: setiap 15 menit = 1 poin. Bagian dari blok dihitung penuh. Poin hanya berupa catatan (tidak memotong gaji).</div>
            </div>
        </div>
    </div>
    <div class="col-12 order-5">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex flex-wrap gap-2 justify-content-end">
                <a class="btn btn-light border" href="{{ route('attendance.index') }}">Batal</a>
                <button class="btn btn-primary px-4" type="submit"><i class="bi bi-save me-2"></i>Simpan Pengaturan</button>
            </div>
        </div>
    </div>
</form>
@endsection
