@extends('layouts.app')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Selamat datang, {{ auth()->user()->name }}</h1>
            <p class="text-secondary mb-0">{{ now()->translatedFormat('l, d F Y') }}</p>
        </div><span
            class="badge rounded-pill text-bg-light border px-3 py-2">{{ auth()->user()->isAdmin() ? 'Administrator' : 'Karyawan' }}</span>
    </div>
    @if ($adminStats)
        <div class="row g-3 mb-4">
            @foreach ([['Total Karyawan', $adminStats['employees'], 'bi-people'], ['Hadir Hari Ini', $adminStats['present'], 'bi-person-check'], ['Terlambat', $adminStats['late'], 'bi-alarm'], ['Pengajuan Menunggu', $adminStats['pendingLeaves'], 'bi-hourglass-split']] as $stat)
                <div class="col-6 col-xl-3">
                    <div class="card stat-card h-100">
                        <div class="card-body d-flex justify-content-between">
                            <div><small class="text-secondary">{{ $stat[0] }}</small>
                                <div class="fs-3 fw-bold mt-1">{{ $stat[1] }}</div>
                            </div><i class="icon bi {{ $stat[2] }}"></i>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
    <div class="row g-4">
        @unless (auth()->user()->isAdmin())
            <div class="col-xl-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h2 class="h5 mb-1">Absensi hari ini</h2>
                                <p class="text-secondary small mb-0">Gunakan waktu server sebagai catatan resmi.</p>
                            </div><span
                                class="badge {{ $attendance?->status === 'late' ? 'text-bg-warning' : ($attendance?->check_in_at ? 'text-bg-success' : 'text-bg-secondary') }}">{{ $attendance?->status === 'late' ? 'Terlambat' : ($attendance?->check_in_at ? 'Hadir' : 'Belum absen') }}</span>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-sm-4">
                                <div class="p-3 rounded-3 bg-light"><small class="text-secondary">Masuk</small><strong
                                        class="d-block fs-4">{{ $attendance?->check_in_at?->format('H:i') ?? '--:--' }}</strong>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="p-3 rounded-3 bg-light"><small class="text-secondary">Pulang</small><strong
                                        class="d-block fs-4">{{ $attendance?->check_out_at?->format('H:i') ?? '--:--' }}</strong>
                                </div>
                            </div>
                            <div class="col-sm-4">
                                <div class="p-3 rounded-3 bg-light"><small class="text-secondary">Keterlambatan</small><strong
                                        class="d-block fs-4">{{ $attendance?->late_minutes ?? 0 }} <small>mnt</small></strong>
                                </div>
                            </div>
                        </div>
                        @if ($attendance?->check_in_at)
                            <div class="d-flex flex-wrap align-items-center gap-2 mb-3"><span class="text-secondary small">Bukti
                                    masuk:</span>
                                @if ($attendance->check_in_photo)
                                    <a href="{{ route('attendance.show', $attendance) }}"><img
                                            src="{{ asset('storage/' . $attendance->check_in_photo) }}" width="30"
                                            height="30" class="rounded border" style="object-fit:cover"
                                            alt="Foto absen masuk"></a><span class="badge text-bg-success"><i
                                            class="bi bi-camera me-1"></i>Foto tersimpan</span>
                                    @endif @if ($attendance->check_in_latitude && $attendance->check_in_longitude)
                                        <a class="text-decoration-none"
                                            href="{{ route('attendance.show', $attendance) }}"><span
                                                class="badge {{ $attendance->check_in_location_valid === false ? 'text-bg-danger' : 'text-bg-success' }}"><i
                                                    class="bi bi-geo-alt me-1"></i>{{ $attendance->check_in_location_valid === false ? 'Di luar area' : ($attendance->check_in_location_valid === true ? 'Lokasi sesuai' : 'Lokasi tercatat') }}</span></a>
                                    @endif
                            </div>
                            @endif @if ($attendance?->check_in_at && !$attendance?->check_out_at)
                                <div class="d-flex flex-wrap align-items-center gap-2 mb-3"><span
                                        class="text-secondary small">Status Pulang:</span>
                                    @if (($checkout['state'] ?? '') === 'allowed')
                                        <span class="badge text-bg-success">Siap Pulang</span>
                                    @elseif(($checkout['state'] ?? '') === 'rejected')
                                        <span class="badge text-bg-danger">Ditolak</span>
                                    @elseif(($checkout['state'] ?? '') === 'too_early')
                                        <span class="badge text-bg-secondary">Belum Waktu Pulang</span>
                                    @elseif(($checkout['state'] ?? '') === 'too_late')
                                    <span class="badge text-bg-secondary">Rentang Berakhir</span> @else<span
                                            class="badge text-bg-warning">Menunggu Persetujuan</span>
                                        @endif @if ($times)
                                            <span class="text-secondary small">Absensi pulang
                                                {{ $times['window_start']->format('H:i') }}–{{ $times['window_end']->format('H:i') }}</span>
                                        @endif
                                </div>
                            @endif
                            <div class="d-flex flex-wrap gap-2">
                                @if (!$attendance?->check_in_at)
                                    <button class="btn btn-primary" type="button" data-bs-toggle="modal"
                                        data-bs-target="#checkInModal"><i class="bi bi-camera me-2"></i>Absen Masuk</button>
                                @elseif(!$attendance?->check_out_at)
                                    @if ($checkout['allowed'] ?? false)
                                        <button class="btn btn-success" type="button" data-bs-toggle="modal"
                                            data-bs-target="#checkOutModal"><i class="bi bi-camera me-2"></i>Absen
                                        Pulang</button> @else<button class="btn btn-secondary" type="button" disabled><i
                                                class="bi bi-lock me-2"></i>Absen Pulang</button>
                                @endif @else<span class="btn btn-light border"><i
                                            class="bi bi-check-circle text-success me-2"></i>Absensi hari ini lengkap</span>
                                @endif
                            </div>
                            @if ($attendance?->check_in_at && !$attendance?->check_out_at)
                                <div class="form-text mt-2">{{ $checkout['reason'] ?? '' }}</div>
                            @endif
                    </div>
                </div>
        </div>@else<div class="col-xl-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-1">Mode Administrator</h2>
                        <p class="text-secondary small mb-0">Absensi hanya untuk karyawan. Pantau kehadiran seluruh karyawan
                            melalui menu Kehadiran atau Laporan.</p><a class="btn btn-primary mt-3"
                            href="{{ route('attendance.index') }}"><i class="bi bi-calendar-check me-2"></i>Pantau
                            Kehadiran</a>
                    </div>
                </div>
            </div>
        @endunless
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h5 mb-4">Ringkasan bulan ini</h2>
                    <div class="d-flex justify-content-between py-3 border-bottom"><span
                            class="text-secondary">Kehadiran</span><strong>{{ $presentCount }} hari</strong></div>
                    <div class="d-flex justify-content-between py-3 border-bottom"><span
                            class="text-secondary">Keterlambatan</span><strong>{{ $lateCount }} kali</strong></div>
                    <div class="d-flex justify-content-between py-3 border-bottom"><span class="text-secondary">Cuti
                            disetujui</span><strong>{{ $leaveCount }} pengajuan</strong></div>
                    <div class="d-flex justify-content-between py-3"><span class="text-secondary">Kunjungan hari ini{{ auth()->user()->isAdmin() ? ' (semua karyawan)' : '' }}</span><strong>{{ $visitCount ?? 0 }} kunjungan</strong></div><a
                        href="{{ route('leave.index') }}" class="btn btn-outline-primary w-100 mt-3">Lihat cuti & izin</a>
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('visits.index') }}" class="btn btn-primary w-100 mt-2">Lihat kunjungan</a>
                    @else
                        <a href="{{ route('visits.index') }}" class="btn btn-primary w-100 mt-2">Catat kunjungan</a>
                    @endif
                </div>
            </div>
        </div>
    </div>
    @include('attendance.capture-modals')
@endsection
