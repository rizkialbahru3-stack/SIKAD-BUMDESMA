@csrf
<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label" for="cor_employee">Karyawan</label>
        <select class="form-select @error('employee_id') is-invalid @enderror" id="cor_employee" name="employee_id" required>
            <option value="">— Pilih karyawan —</option>
            @foreach($employees as $employee)
                <option value="{{ $employee->id }}" @selected((string) old('employee_id', $attendance->employee_id ?? '') === (string) $employee->id)>
                    {{ $employee->employee_code }} — {{ $employee->display_name }}@unless($employee->is_active) (nonaktif)@endunless
                </option>
            @endforeach
        </select>
        @error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="cor_date">Tanggal</label>
        <input class="form-control @error('attendance_date') is-invalid @enderror" id="cor_date" type="date" name="attendance_date" value="{{ old('attendance_date', isset($attendance) ? $attendance->attendance_date->format('Y-m-d') : now()->toDateString()) }}" max="{{ now()->toDateString() }}" required>
        @error('attendance_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="cor_in">Jam masuk</label>
        <input class="form-control @error('check_in') is-invalid @enderror" id="cor_in" type="time" name="check_in" value="{{ old('check_in', isset($attendance) && $attendance->check_in_at ? $attendance->check_in_at->format('H:i') : '') }}">
        @error('check_in')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="cor_out">Jam pulang <span class="text-secondary">(boleh kosong)</span></label>
        <input class="form-control @error('check_out') is-invalid @enderror" id="cor_out" type="time" name="check_out" value="{{ old('check_out', isset($attendance) && $attendance->check_out_at ? $attendance->check_out_at->format('H:i') : '') }}">
        @error('check_out')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
    <div class="col-md-6">
        <label class="form-label" for="cor_status">Status</label>
        <select class="form-select @error('status') is-invalid @enderror" id="cor_status" name="status" required>
            @foreach(['present' => 'Hadir', 'late' => 'Terlambat', 'leave' => 'Cuti', 'permission' => 'Izin', 'sick' => 'Sakit', 'absent' => 'Alpa'] as $value => $label)
                <option value="{{ $value }}" @selected(old('status', $attendance->status ?? 'present') === $value)>{{ $label }}</option>
            @endforeach
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
        <div class="form-text">Bila jam masuk diisi, status Hadir/Terlambat ditentukan otomatis dari jam kerja. Kosongkan kedua jam untuk Cuti/Izin/Sakit/Alpa.</div>
    </div>
    <div class="col-md-6">
        <label class="form-label" for="cor_note">Keterangan koreksi</label>
        <textarea class="form-control @error('note') is-invalid @enderror" id="cor_note" name="note" rows="3" maxlength="500" placeholder="cth. Lupa absen, HP mati, GPS bermasalah...">{{ old('note', $attendance->note ?? '') }}</textarea>
        @error('note')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>
</div>
