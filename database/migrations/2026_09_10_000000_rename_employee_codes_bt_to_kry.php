<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const MAP = [
        'BT-001' => 'KRY-001',
        'BT-002' => 'KRY-002',
        'BT-003' => 'KRY-003',
        'BT-004' => 'KRY-004',
        'BT-005' => 'KRY-005',
        'BT-006' => 'KRY-006',
    ];

    public function up(): void
    {
        $this->rename(self::MAP);
    }

    public function down(): void
    {
        $this->rename(array_flip(self::MAP));
    }

    private function rename(array $map): void
    {
        foreach ($map as $from => $to) {
            $exists = DB::table('employees')->where('employee_code', $from)->exists();
            $taken = DB::table('employees')->where('employee_code', $to)->exists();

            // Lewati bila kode asal tidak ada atau kode tujuan sudah dipakai
            // (menghindari pelanggaran unique constraint).
            if ($exists && ! $taken) {
                DB::table('employees')->where('employee_code', $from)->update(['employee_code' => $to]);
            }
        }
    }
};
