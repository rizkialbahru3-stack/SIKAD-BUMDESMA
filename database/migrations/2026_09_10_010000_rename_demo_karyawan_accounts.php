<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const MAP = [
        'karyawan@bumdesma.test' => ['email' => 'kry-001@bumdesma.test', 'name' => 'Karyawan 1'],
        'karyawan2@bumdesma.test' => ['email' => 'kry-002@bumdesma.test', 'name' => 'Karyawan 2'],
        'karyawan3@bumdesma.test' => ['email' => 'kry-003@bumdesma.test', 'name' => 'Karyawan 3'],
        'karyawan4@bumdesma.test' => ['email' => 'kry-004@bumdesma.test', 'name' => 'Karyawan 4'],
        'karyawan5@bumdesma.test' => ['email' => 'kry-005@bumdesma.test', 'name' => 'Karyawan 5'],
        'karyawan6@bumdesma.test' => ['email' => 'kry-006@bumdesma.test', 'name' => 'Karyawan 6'],
    ];

    public function up(): void
    {
        foreach (self::MAP as $from => $to) {
            $exists = DB::table('users')->where('email', $from)->exists();
            $taken = DB::table('users')->where('email', $to['email'])->exists();

            // Lewati bila akun lama tidak ada atau email tujuan sudah dipakai
            // (menghindari pelanggaran unique constraint).
            if ($exists && ! $taken) {
                DB::table('users')->where('email', $from)->update([
                    'email' => $to['email'],
                    'name' => $to['name'],
                ]);
            }
        }
    }

    public function down(): void
    {
        foreach (self::MAP as $from => $to) {
            $exists = DB::table('users')->where('email', $to['email'])->exists();
            $taken = DB::table('users')->where('email', $from)->exists();

            if ($exists && ! $taken) {
                DB::table('users')->where('email', $to['email'])->update(['email' => $from]);
            }
        }
    }
};
