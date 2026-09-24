@extends('layouts.app')

@section('content')
<div class="mb-4">
    <a class="text-decoration-none" href="{{ route('leave.index') }}">
        <i class="bi bi-arrow-left me-2">
        </i>Kembali ke Daftar Pengajuan</a>
    <h1 class="h3 mt-3 mb-1">Detail Pengajuan Cuti & Izin</h1>
    <p class="text-secondary mb-0">Diajukan pada {{ $leaveRequest->created_at->format('d M Y H:i') }}.</p>
</div>

<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm text-center p-4 mb-4">
            <div class="employee-avatar mx-auto mb-3" style="width:110px;height:110px;font-size:2.2rem">
                @if($leaveRequest->employee?->photo)
                    <img
                        src="{{ asset('storage/'.$leaveRequest->employee->photo) }}"
                        alt="Foto {{ $leaveRequest->employee?->display_name }}">
                @else
                    <span>{{ strtoupper(substr($leaveRequest->employee?->display_name ?? '-', 0, 1)) }}</span>
                @endif
            </div>
            <h2 class="h5 mb-1">{{ $leaveRequest->employee?->display_name ?? '-' }}</h2>
            <p class="text-secondary mb-2">{{ $leaveRequest->employee?->position?->name ?? 'Belum ada jabatan' }}</p>
            <span class="badge text-bg-light border">{{ $leaveRequest->employee?->employee_code ?? '-' }}</span>
        </div>

        @if($quota)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h2 class="h5 mb-0">
                        <i class="bi bi-pie-chart me-2 text-primary">
                        </i>Kuota Cuti</h2>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-secondary">Hak Cuti</span>
                        <strong>
                            {{ $quota['entitlement'] }} Hari</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-secondary">Sudah Digunakan</span>
                        <strong>
                            {{ $quota['used'] }} Hari</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-secondary">Sedang Diajukan</span>
                        <strong>
                            {{ $quota['pending'] }} Hari</strong>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span class="text-secondary">Sisa Cuti</span>
                        <strong class="text-success">
                            {{ $quota['remaining'] }} Hari</strong>
                    </div>
                </div>
            </div>
        @endif

        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h2 class="h5 mb-0">
                    <i class="bi bi-clock-history me-2 text-primary">
                    </i>Riwayat Pengajuan</h2>
            </div>
            <div class="card-body">
                @forelse($history as $item)
                    <div class="d-flex justify-content-between align-items-center gap-2 py-2 @unless($loop->last) border-bottom @endunless">
                        <div>
                            <strong class="d-block small">{{ $item->start_date->format('d M Y') }}</strong>
                            <small class="text-secondary">
                                {{ $item->type === 'leave' ? 'Cuti' : ($item->type === 'sick' ? 'Sakit' : 'Izin') }} · {{ $item->total_days }} hari</small>
                        </div>
                        @if($item->status === 'approved')
                            <span class="badge text-bg-success">Disetujui</span>
                        @elseif($item->status === 'rejected')
                            <span class="badge text-bg-danger">Ditolak</span>
                        @else
                            <span class="badge text-bg-warning">Menunggu</span>
                        @endif
                    </div>
                @empty
                    <p class="text-secondary small mb-0">Belum ada riwayat lain.</p>
                @endforelse
                @if($history->isNotEmpty() && $leaveRequest->employee)
                    <a
                        class="btn btn-outline-primary btn-sm w-100 mt-3"
                        href="{{ route('leave.index', ['search' => $leaveRequest->employee->employee_code]) }}">Lihat
                        Semua
                        Riwayat</a>
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h2 class="h5 mb-0">Informasi Pengajuan</h2>
                @if($leaveRequest->status === 'approved')
                    <span class="badge text-bg-success">Disetujui</span>
                @elseif($leaveRequest->status === 'rejected')
                    <span class="badge text-bg-danger">Ditolak</span>
                @else
                    <span class="badge text-bg-warning">Menunggu Persetujuan</span>
                @endif
            </div>
            <div class="card-body p-4">
                <div class="row g-4 mb-4">
                    <div class="col-sm-6">
                        <small class="text-secondary d-block">Jenis Pengajuan</small>
                        <strong>
                            {{ $leaveRequest->type === 'leave' ? 'Cuti' : ($leaveRequest->type === 'sick' ? 'Sakit' : 'Izin') }}
                        </strong>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-secondary d-block">Jumlah Hari</small>
                        <strong>
                            {{ $leaveRequest->total_days }} Hari</strong>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-secondary d-block">Tanggal Mulai</small>
                        <strong>
                            {{ $leaveRequest->start_date->format('d F Y') }}
                        </strong>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-secondary d-block">Tanggal Selesai</small>
                        <strong>
                            {{ $leaveRequest->end_date->format('d F Y') }}
                        </strong>
                    </div>
                    <div class="col-12">
                        <small class="text-secondary d-block">Alasan</small>
                        <p class="mb-0">
                            {{ $leaveRequest->reason }}
                        </p>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-secondary d-block">Lampiran</small>
                        @if($leaveRequest->attachment_path)
                            <a href="{{ route('leave.attachment', $leaveRequest) }}">
                                <i class="bi bi-paperclip me-1">
                                </i>Unduh dokumen</a>
                        @else
                            <span class="text-secondary">-</span>
                        @endif
                    </div>
                </div>

                @if($leaveRequest->status !== 'pending')
                    <div class="alert @if($leaveRequest->status === 'approved') alert-success @else alert-danger @endif mb-0">
                        @if($leaveRequest->status === 'approved')
                            <strong class="d-block mb-1">Pengajuan telah disetujui</strong>
                        @else
                            <strong class="d-block mb-1">Pengajuan ditolak</strong>
                        @endif
                        <small
                            class="d-block">Oleh
                            {{ $leaveRequest->reviewer?->name ?? '-' }}
                            pada
                            {{ $leaveRequest->reviewed_at?->format('d M Y H:i') ?? '-' }}.</small>
                        @if($leaveRequest->review_note)
                            <small class="d-block mt-1">Catatan Admin: {{ $leaveRequest->review_note }}</small>
                        @endif
                        @if($leaveRequest->rejection_reason)
                            <small class="d-block mt-1">Alasan penolakan: {{ $leaveRequest->rejection_reason }}</small>
                        @endif
                    </div>
                @endif

                @if($leaveRequest->status === 'pending' && auth()->user()->isAdmin())
                    <div class="d-flex flex-wrap gap-2 mt-4">
                        <button
                            class="btn btn-success"
                            type="button"
                            data-bs-toggle="modal"
                            data-bs-target="#approveModal">
                        <i class="bi bi-check-lg me-2">
                        </i>Setujui</button>
                        <button
                            class="btn btn-outline-danger"
                            type="button"
                            data-bs-toggle="modal"
                            data-bs-target="#rejectModal">
                        <i class="bi bi-x-lg me-2">
                        </i>Tolak</button>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if($leaveRequest->status === 'pending' && auth()->user()->isAdmin())
    <div class="modal fade" id="approveModal" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content" method="POST" action="{{ route('leave.review', $leaveRequest) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="approved">
                <div class="modal-header">
                    <h5 class="modal-title">Setujui Pengajuan</h5>
                    <button class="btn-close" data-bs-dismiss="modal" type="button">
                    </button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menyetujui pengajuan ini?</p>
                    <div class="bg-light rounded p-3 mb-3 small">
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-secondary">Nama</span>
                            <strong>
                                {{ $leaveRequest->employee?->display_name }}
                            </strong>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-secondary">Jenis</span>
                            <strong>
                                {{ $leaveRequest->type === 'leave' ? 'Cuti' : ($leaveRequest->type === 'sick' ? 'Sakit' : 'Izin') }}
                            </strong>
                        </div>
                        <div class="d-flex justify-content-between py-1">
                            <span class="text-secondary">Periode</span>
                            <strong>
                                {{ $leaveRequest->start_date->format('d M Y') }} - {{ $leaveRequest->end_date->format('d M Y') }}
                            </strong>
                        </div>
                    </div>
                    <label class="form-label" for="review_note">Catatan Admin (opsional)</label>
                    <textarea class="form-control" id="review_note" name="review_note" rows="2">
                    </textarea>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-light border" data-bs-dismiss="modal" type="button">Batal</button>
                    <button class="btn btn-success" type="submit">
                        <i class="bi bi-check-lg me-2">
                        </i>Setujui Pengajuan</button>
                </div>
            </form>
        </div>
    </div>

    <div class="modal fade" id="rejectModal" tabindex="-1">
        <div class="modal-dialog">
            <form class="modal-content" method="POST" action="{{ route('leave.review', $leaveRequest) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="rejected">
                <div class="modal-header">
                    <h5 class="modal-title">Tolak Pengajuan</h5>
                    <button class="btn-close" data-bs-dismiss="modal" type="button">
                    </button>
                </div>
                <div class="modal-body">
                    <label class="form-label" for="rejection_reason">Alasan Penolakan <span class="text-danger">*</span>
                    </label>
                    <textarea
                        class="form-control"
                        id="rejection_reason"
                        name="rejection_reason"
                        rows="3"
                        minlength="10"
                        required>
                    </textarea>
                    <div class="form-text">Wajib diisi, minimal 10 karakter.</div>
                    <label class="form-label mt-3" for="reject_note">Catatan Admin (opsional)</label>
                    <textarea class="form-control" id="reject_note" name="review_note" rows="2">
                    </textarea>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-light border" data-bs-dismiss="modal" type="button">Batal</button>
                    <button class="btn btn-danger" type="submit">
                        <i class="bi bi-x-lg me-2">
                        </i>Tolak Pengajuan</button>
                </div>
            </form>
        </div>
    </div>
@endif
@endsection
