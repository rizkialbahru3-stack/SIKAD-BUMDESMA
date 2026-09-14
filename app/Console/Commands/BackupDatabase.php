<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

class BackupDatabase extends Command
{
    protected $signature = 'db:backup
        {--keep=7 : Jumlah file backup terbaru yang dipertahankan}';

    protected $description = 'Backup database MySQL ke storage/app/backups (dijadwalkan harian via scheduler).';

    public function handle(): int
    {
        if (config('database.default') !== 'mysql') {
            $this->error('Perintah ini hanya mendukung koneksi database mysql.');

            return self::FAILURE;
        }

        $dir = storage_path('app/backups');
        File::ensureDirectoryExists($dir);

        $timestamp = Carbon::now()->format('Y-m-d_H-i-s');
        $db = config('database.connections.mysql.database');
        $file = "{$dir}/{$db}_{$timestamp}.sql";

        $process = new Process([
            'mysqldump',
            '--host='.config('database.connections.mysql.host'),
            '--port='.config('database.connections.mysql.port'),
            '--user='.config('database.connections.mysql.username'),
            '--password='.config('database.connections.mysql.password'),
            '--single-transaction',
            '--routines',
            $db,
        ]);
        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful()) {
            $this->error('Backup gagal: '.trim($process->getErrorOutput()));

            return self::FAILURE;
        }

        File::put($file, $process->getOutput());

        $keep = max(1, (int) $this->option('keep'));
        $files = collect(File::files($dir))
            ->filter(fn ($f) => $f->getExtension() === 'sql')
            ->sortByDesc(fn ($f) => $f->getFilename());
        foreach ($files->slice($keep) as $old) {
            File::delete($old->getPathname());
        }

        $this->info("Backup tersimpan: {$file} (dipertahankan {$keep} file terbaru).");

        return self::SUCCESS;
    }
}
