@extends('layouts.app')

@section('content')
    <div class="mb-4 d-flex flex-wrap gap-2 align-items-center">
        <a class="text-decoration-none me-auto" href="{{ route('visits.index') }}">
            <i class="bi bi-arrow-left me-2"></i>Kembali ke Absen Kunjungan</a>
        <form class="d-inline" method="POST" action="{{ route('visits.destroy', $visit) }}" data-confirm="Hapus data kunjungan ini? Foto ikut terhapus permanen.">
            @csrf
            @method('DELETE')
            <button class="btn btn-sm btn-outline-danger" type="submit">
                <i class="bi bi-trash me-1"></i>Hapus</button>
        </form>
    </div>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h2 class="h5 mb-0"><i class="bi bi-geo-alt me-2 text-primary"></i>Detail Kunjungan</h2>
                </div>
                <div class="card-body">
                    <a href="{{ asset('storage/' . $visit->photo) }}" target="_blank">
                        <img src="{{ asset('storage/' . $visit->photo) }}" class="img-fluid rounded-3 border w-100 mb-3" style="max-height:320px;object-fit:cover" alt="Foto kunjungan">
                    </a>
                    <dl class="row small mb-0">
                        <dt class="col-4 text-secondary">Judul</dt>
                        <dd class="col-8 fw-semibold">{{ $visit->title }}</dd>
                        @if($visit->description)
                        <dt class="col-4 text-secondary">Deskripsi</dt>
                        <dd class="col-8">{{ $visit->description }}</dd>
                        @endif
                        <dt class="col-4 text-secondary">Tanggal</dt>
                        <dd class="col-8">{{ $visit->visit_date->translatedFormat('l, d F Y') }}</dd>
                        <dt class="col-4 text-secondary">Karyawan</dt>
                        <dd class="col-8">{{ $visit->employee?->user?->name ?? '-' }} <span class="text-secondary">({{ $visit->employee?->employee_code }})</span></dd>
                        <dt class="col-4 text-secondary">Koordinat</dt>
                        <dd class="col-8">
                            @if($visit->latitude && $visit->longitude)
                                {{ number_format($visit->latitude, 6) }}, {{ number_format($visit->longitude, 6) }}
                                @if($visit->accuracy !== null)
                                    <span class="text-secondary">(±{{ $visit->accuracy }} m)</span>
                                @endif
                            @else
                                <span class="text-secondary">-</span>
                            @endif
                        </dd>
                    </dl>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h2 class="h5 mb-0"><i class="bi bi-map me-2 text-primary"></i>Peta Lokasi</h2>
                </div>
                <div class="card-body">
                    @if($visit->latitude && $visit->longitude)
                        <div id="visitMap" style="height:420px;border-radius:.9rem;z-index:0"></div>
                        <div class="mt-3">
                            <a class="btn btn-sm btn-outline-primary" target="_blank" href="https://www.openstreetmap.org/?mlat={{ $visit->latitude }}&mlon={{ $visit->longitude }}#map=17/{{ $visit->latitude }}/{{ $visit->longitude }}">
                                <i class="bi bi-box-arrow-up-right me-1"></i>Buka di OpenStreetMap</a>
                            <a class="btn btn-sm btn-outline-secondary" target="_blank" href="https://www.google.com/maps?q={{ $visit->latitude }},{{ $visit->longitude }}">
                                <i class="bi bi-box-arrow-up-right me-1"></i>Buka di Google Maps</a>
                        </div>
                    @else
                        <div class="alert alert-secondary mb-0">Kunjungan ini belum memiliki koordinat GPS (data lama sebelum fitur lokasi).</div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
@if($visit->latitude && $visit->longitude)
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function () {
    const lat = {{ (float) $visit->latitude }}, lng = {{ (float) $visit->longitude }};
    const rawTitle = @json($visit->title);
    const title = document.createElement('div');
    title.textContent = rawTitle;
    const map = L.map('visitMap').setView([lat, lng], 16);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(map);
    L.marker([lat, lng]).addTo(map).bindPopup(title).openPopup();
})();
</script>
@endif
@endpush
