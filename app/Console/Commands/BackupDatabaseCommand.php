<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Storage;

class BackupDatabaseCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'db:backup
                            {--keep=7 : Hapus backup lebih dari N hari}
                            {--mysqldump-path= : Path lengkap ke mysqldump (opsional, default: mysqldump dari PATH)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup database MySQL ke file SQL di storage/app/backups. Kompatibel dengan Laragon dan cPanel.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dbHost = config('database.connections.mysql.host');
        $dbPort = config('database.connections.mysql.port');
        $dbName = config('database.connections.mysql.database');
        $dbUser = config('database.connections.mysql.username');
        $dbPass = config('database.connections.mysql.password');

        // Tentukan path mysqldump
        $mysqldumpPath = $this->option('mysqldump-path') ?: $this->findMysqldump();

        if (! $mysqldumpPath) {
            $this->error('mysqldump tidak ditemukan. Gunakan opsi --mysqldump-path untuk menentukan path lengkap.');

            return self::FAILURE;
        }

        // Siapkan direktori backup
        $backupDir = storage_path('app/backups');
        if (! is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $timestamp = Carbon::now('Asia/Jakarta')->format('Y-m-d_His');
        $filename = "{$dbName}_{$timestamp}.sql";
        $filepath = "{$backupDir}/{$filename}";

        $this->info("Memulai backup database '{$dbName}'...");

        // Susun perintah mysqldump
        $command = [
            $mysqldumpPath,
            "--host={$dbHost}",
            "--port={$dbPort}",
            "--user={$dbUser}",
            '--single-transaction',
            '--routines',
            '--triggers',
            '--quick',
            $dbName,
        ];

        // Tambahkan password jika ada (password kosong untuk Laragon default)
        $env = [];
        if (! empty($dbPass)) {
            $env['MYSQL_PWD'] = $dbPass;
        }

        try {
            $result = Process::env($env)
                ->timeout(300)
                ->run(implode(' ', array_map('escapeshellarg', $command)));

            if ($result->successful()) {
                file_put_contents($filepath, $result->output());
                $fileSize = $this->formatFileSize(filesize($filepath));
                $this->info("✅ Backup berhasil: {$filename} ({$fileSize})");
                Log::info("db:backup berhasil — file: {$filename}, ukuran: {$fileSize}");
            } else {
                $this->error('Backup gagal: ' . $result->errorOutput());
                Log::error('db:backup gagal', ['error' => $result->errorOutput()]);

                return self::FAILURE;
            }
        } catch (\Throwable $e) {
            $this->error("Backup gagal: {$e->getMessage()}");
            Log::error('db:backup exception', ['error' => $e->getMessage()]);

            return self::FAILURE;
        }

        // Hapus backup lama
        $this->cleanOldBackups($backupDir);

        return self::SUCCESS;
    }

    /**
     * Cari mysqldump di lokasi umum (Laragon, XAMPP, PATH).
     */
    protected function findMysqldump(): ?string
    {
        // Cek PATH dulu
        $whereCmd = PHP_OS_FAMILY === 'Windows' ? 'where mysqldump 2>NUL' : 'which mysqldump 2>/dev/null';
        $output = shell_exec($whereCmd);

        if ($output && trim($output)) {
            return trim(explode("\n", $output)[0]);
        }

        // Cek lokasi umum Laragon
        $commonPaths = [
            'C:/laragon/bin/mysql/mysql-8.4.3-winx64/bin/mysqldump.exe',
            'C:/laragon/bin/mysql/mysql-8.0.30-winx64/bin/mysqldump.exe',
        ];

        // Scan dinamis folder Laragon MySQL
        $laragonMysqlDir = 'C:/laragon/bin/mysql';
        if (is_dir($laragonMysqlDir)) {
            $dirs = glob("{$laragonMysqlDir}/mysql-*/bin/mysqldump.exe");
            $commonPaths = array_merge($dirs ?: [], $commonPaths);
        }

        // Lokasi umum XAMPP
        $commonPaths[] = 'C:/xampp/mysql/bin/mysqldump.exe';

        foreach ($commonPaths as $path) {
            if (file_exists($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Hapus backup yang lebih lama dari N hari.
     */
    protected function cleanOldBackups(string $backupDir): void
    {
        $keepDays = (int) $this->option('keep');
        $cutoff = Carbon::now()->subDays($keepDays)->timestamp;
        $deletedCount = 0;

        $files = glob("{$backupDir}/*.sql");

        foreach ($files ?: [] as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
                $deletedCount++;
            }
        }

        if ($deletedCount > 0) {
            $this->info("🗑️  Backup lama dihapus: {$deletedCount} file (lebih dari {$keepDays} hari).");
        }
    }

    /**
     * Format ukuran file menjadi terbaca manusia.
     */
    protected function formatFileSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor((strlen((string) $bytes) - 1) / 3);

        return sprintf('%.1f %s', $bytes / pow(1024, $factor), $units[$factor] ?? 'TB');
    }
}
