@extends('layouts.app')

@section('content')
<div class="mb-4">
    <a class="text-decoration-none" href="{{ route('attendance.index') }}"><i class="bi bi-arrow-left me-2"></i>Kembali ke Data Kehadiran</a>
    <h1 class="h3 mt-3 mb-1">Input Absensi Manual</h1>
    <p class="text-secondary mb-0">Catat kehadiran yang terlewat (lupa absen, kendala HP/GPS). Keterlambatan dan total jam kerja dihitung otomatis dari jam kerja karyawan.</p>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-4">
        <form method="POST" action="{{ route('attendance.corrections.store') }}">
            @include('attendance.corrections._form')
            <div class="d-flex gap-2 mt-4">
                <button class="btn btn-primary" type="submit"><i class="bi bi-save me-2"></i>Simpan Absensi</button>
                <a class="btn btn-light border" href="{{ route('attendance.index') }}">Batal</a>
            </div>
        </form>
    </div>
</div>
@endsection
