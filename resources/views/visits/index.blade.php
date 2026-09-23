@extends('layouts.app')

@section('content')
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
        <div>
            <h1 class="h3 mb-1">Absen Kunjungan</h1>
            <p class="text-secondary mb-0">Catat kunjungan lapangan dengan judul dan foto. Maksimal {{ $maxPerMonth }} kunjungan per bulan per karyawan.</p>
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-xl-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <h2 class="h6 mb-1">Rekap {{ $monthNav['label'] }}</h2>
                    @unless(auth()->user()->isAdmin())
                        <div class="d-flex align-items-end gap-2 mb-2">
                            <span class="fs-2 fw-bold">{{ $myCount }}</span>
                            <span class="text-secondary mb-2">/ {{ $maxPerMonth }} kunjungan</span>
                            <span class="badge {{ $remaining > 0 ? 'text-bg-success' : 'text-bg-danger' }} ms-auto mb-2">
                                Sisa {{ $remaining }}
                            </span>
                        </div>
                        <div class="progress mb-3" style="height:10px">
                            <div class="progress-bar" role="progressbar" style="width: {{ $maxPerMonth ? round($myCount / $maxPerMonth * 100) : 0 }}%" aria-valuenow="{{ $myCount }}" aria-valuemin="0" aria-valuemax="{{ $maxPerMonth }}"></div>
                        </div>
                        <p class="text-secondary small mb-0">Kuota dihitung per bulan kalender berdasarkan tanggal kunjungan.</p>
                    @else
                        <p class="text-secondary small mb-3">Ringkasan jumlah kunjungan tiap karyawan pada bulan ini.</p>
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Karyawan</th>
                                        <th class="text-center">Jumlah</th>
                                        <th class="text-center">Sisa</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($summary as $row)
                                        <tr>
                                            <td>
                                                <strong>{{ $row['employee']->user?->name ?? '-' }}</strong>
                                                <small class="d-block text-secondary">{{ $row['employee']->employee_code }}</small>
                                            </td>
                                            <td class="text-center">{{ $row['count'] }}/{{ $maxPerMonth }}</td>
                                            <td class="text-center">
                                                <span class="badge {{ $row['remaining'] > 0 ? 'text-bg-success' : 'text-bg-danger' }}">{{ $row['remaining'] }}</span>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="text-center text-secondary py-3">Belum ada karyawan aktif.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    @endunless
                </div>
            </div>
        </div>
        <div class="col-xl-8">
            @unless(auth()->user()->isAdmin())
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h2 class="h6 mb-3">Catat kunjungan baru</h2>
                        @if($remaining <= 0)
                            <div class="alert alert-warning mb-0">Kuota kunjungan bulan {{ $monthNav['label'] }} sudah penuh ({{ $maxPerMonth }}/{{ $maxPerMonth }}). Anda dapat mencatat lagi bulan depan.</div>
                        @else
                            <form method="POST" action="{{ route('visits.store') }}" enctype="multipart/form-data" class="row g-3">
                                @csrf
                                <div class="col-md-6">
                                    <label class="form-label small" for="visit-title">Judul kunjungan</label>
                                    <input class="form-control @error('title') is-invalid @enderror" id="visit-title" name="title" value="{{ old('title') }}" maxlength="255" placeholder="cth. Kunjungan ke kelompok tani Desa Tarub" required>
                                    @error('title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small" for="visit-date">Tanggal kunjungan</label>
                                    <input class="form-control @error('visit_date') is-invalid @enderror" id="visit-date" name="visit_date" type="date" value="{{ old('visit_date', now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
                                    @error('visit_date')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small" for="visit-photo">Foto kunjungan</label>
                                    <input class="form-control @error('photo') is-invalid @enderror" id="visit-photo" name="photo" type="file" accept="image/jpeg,image/png,image/webp" required>
                                    @error('photo')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">JPG/PNG/WebP, maks 2 MB.</div>
                                </div>
                                <div class="col-12">
                                    <button class="btn btn-primary" type="submit">
                                        <i class="bi bi-geo-alt me-2"></i>Simpan kunjungan (sisa {{ $remaining }})
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                </div>
            @else
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h2 class="h6 mb-3">Filter data</h2>
                        <form method="GET" class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label small">Bulan</label>
                                <input class="form-control" name="month" type="month" value="{{ $filters['month'] }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Karyawan</label>
                                <select class="form-select" name="employee_id">
                                    <option value="">Semua karyawan</option>
                                    @foreach ($employees as $employee)
                                        <option value="{{ $employee->id }}" @selected((string) ($filters['employee_id'] ?? '') === (string) $employee->id)>
                                            {{ $employee->employee_code }} — {{ $employee->user?->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small">Cari judul</label>
                                <input class="form-control" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Judul kunjungan">
                            </div>
                            <div class="col-12 d-flex gap-2">
                                <button class="btn btn-primary" type="submit"><i class="bi bi-search"></i></button>
                                <a class="btn btn-light border" href="{{ route('visits.index') }}"><i class="bi bi-arrow-counterclockwise"></i></a>
                            </div>
                        </form>
                    </div>
                </div>
            @endunless
        </div>
    </div>

    @unless(auth()->user()->isAdmin())
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3 align-items-end">
                    <div class="col-6 col-lg-3">
                        <label class="form-label small">Bulan</label>
                        <input class="form-control" name="month" type="month" value="{{ $filters['month'] }}">
                    </div>
                    <div class="col-6 col-lg-4">
                        <label class="form-label small">Cari judul</label>
                        <input class="form-control" name="search" value="{{ $filters['search'] ?? '' }}" placeholder="Judul kunjungan">
                    </div>
                    <div class="col-12 col-lg-2 d-flex gap-2">
                        <button class="btn btn-primary flex-grow-1" type="submit"><i class="bi bi-search"></i></button>
                        <a class="btn btn-light border" href="{{ route('visits.index') }}"><i class="bi bi-arrow-counterclockwise"></i></a>
                    </div>
                </form>
            </div>
        </div>
    @endunless

    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
        <a class="btn btn-light border btn-sm" href="{{ route('visits.index', array_merge(request()->except(['page']), ['month' => $monthNav['prev']])) }}">
            <i class="bi bi-chevron-left"></i> {{ $monthNav['prevLabel'] }}
        </a>
        <span class="badge text-bg-light border px-3 py-2">{{ $monthNav['label'] }}</span>
        @if (!$monthNav['isCurrent'])
            <a class="btn btn-light border btn-sm" href="{{ route('visits.index', array_merge(request()->except(['page']), ['month' => $monthNav['next']])) }}">
                {{ $monthNav['nextLabel'] }} <i class="bi bi-chevron-right"></i>
            </a>
            <a class="btn btn-outline-primary btn-sm" href="{{ route('visits.index') }}">Bulan ini</a>
        @endif
    </div>

    <div class="card border-0 shadow-sm">
        <div class="table-responsive">
            <table class="table align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        @if(auth()->user()->isAdmin())
                            <th>Karyawan</th>
                        @endif
                        <th>Judul kunjungan</th>
                        <th>Tanggal</th>
                        <th>Foto</th>
                        <th class="text-end">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($visits as $visit)
                        <tr>
                            @if(auth()->user()->isAdmin())
                                <td>
                                    <strong>{{ $visit->employee?->user?->name ?? '-' }}</strong>
                                    <small class="d-block text-secondary">{{ $visit->employee?->employee_code }}</small>
                                </td>
                            @endif
                            <td>{{ $visit->title }}</td>
                            <td>{{ $visit->visit_date->translatedFormat('d M Y') }}</td>
                            <td>
                                <a href="{{ asset('storage/' . $visit->photo) }}" target="_blank" title="Lihat foto">
                                    <img src="{{ asset('storage/' . $visit->photo) }}" width="48" height="48" class="rounded border" style="object-fit:cover" alt="Foto kunjungan">
                                </a>
                            </td>
                            <td class="text-end text-nowrap">
                                <a class="btn btn-sm btn-outline-secondary" href="{{ asset('storage/' . $visit->photo) }}" target="_blank" title="Lihat foto penuh">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <form class="d-inline" method="POST" action="{{ route('visits.destroy', $visit) }}" data-confirm="Hapus data kunjungan ini? Foto ikut terhapus permanen.">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" type="submit" title="Hapus">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()->isAdmin() ? 5 : 4 }}" class="text-center text-secondary py-5">
                                <i class="bi bi-geo-alt d-block fs-2 mb-2"></i>Belum ada data kunjungan pada bulan ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $visits->links() }}</div>
    </div>
@endsection
