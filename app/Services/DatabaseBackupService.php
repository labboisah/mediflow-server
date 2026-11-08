<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\Process\Process;

class DatabaseBackupService
{
    public function databaseSize(): array
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");

        if (($config['driver'] ?? '') === 'sqlite') {
            $path = $this->absolutePath((string) $config['database'], database_path());
            $bytes = is_file($path) ? filesize($path) : 0;

            return [
                'bytes' => $bytes,
                'formatted' => $this->formatBytes($bytes),
            ];
        }

        $database = $config['database'] ?? null;

        $bytes = (int) DB::selectOne(
            'select coalesce(sum(data_length + index_length), 0) as size from information_schema.tables where table_schema = ?',
            [$database]
        )->size;

        return [
            'bytes' => $bytes,
            'formatted' => $this->formatBytes($bytes),
        ];
    }

    public function backup(): array
    {
        $connection = config('database.default');
        $config = config("database.connections.{$connection}");
        $driver = $config['driver'] ?? '';

        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException('Database backup currently supports MySQL and MariaDB connections.');
        }

        $target = $this->backupTargetDirectory();
        $filename = $this->backupFilename((string) $config['database']);
        $path = $target['directory'] . DIRECTORY_SEPARATOR . $filename;

        $this->runMysqlDump($config, $path);

        return [
            'path' => $path,
            'filename' => $filename,
            'target' => $target['label'],
            'size' => $this->formatBytes(filesize($path) ?: 0),
        ];
    }

    private function runMysqlDump(array $config, string $path): void
    {
        $mysqldump = $this->mysqldumpPath();
        $process = new Process(array_values(array_filter([
            $mysqldump,
            '--host=' . ($config['host'] ?? '127.0.0.1'),
            '--port=' . ($config['port'] ?? 3306),
            '--user=' . ($config['username'] ?? 'root'),
            ($config['password'] ?? '') !== '' ? '--password=' . $config['password'] : null,
            '--single-transaction',
            '--routines',
            '--triggers',
            '--no-tablespaces',
            '--result-file=' . $path,
            $config['database'],
        ])));

        $process->setTimeout(300);
        $process->run();

        if (! $process->isSuccessful() || ! is_file($path) || filesize($path) === 0) {
            @unlink($path);
            throw new RuntimeException(trim($process->getErrorOutput()) ?: 'Database backup failed.');
        }
    }

    private function backupTargetDirectory(): array
    {
        $usbDrive = $this->firstUsbDrive();

        if ($usbDrive) {
            $directory = rtrim($usbDrive, '\\/') . DIRECTORY_SEPARATOR . 'FAYHOS_Backups';
            $this->ensureDirectory($directory);

            return [
                'directory' => $directory,
                'label' => 'USB drive',
            ];
        }

        $directory = $this->downloadsDirectory();
        $this->ensureDirectory($directory);

        return [
            'directory' => $directory,
            'label' => 'Downloads folder',
        ];
    }

    private function firstUsbDrive(): ?string
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return null;
        }

        $output = shell_exec('wmic logicaldisk where "DriveType=2" get DeviceID /value 2>NUL');
        $drive = $this->driveFromWindowsOutput((string) $output, '/DeviceID=([A-Z]:)/i');

        if ($drive) {
            return $drive;
        }

        $output = shell_exec("powershell -NoProfile -Command \"Get-CimInstance Win32_LogicalDisk -Filter 'DriveType=2' | Select-Object -ExpandProperty DeviceID\" 2>\$null");

        return $this->driveFromWindowsOutput((string) $output, '/^([A-Z]:)$/i');
    }

    private function driveFromWindowsOutput(string $output, string $pattern): ?string
    {
        foreach (preg_split('/\R+/', $output) as $line) {
            if (preg_match($pattern, trim($line), $matches)) {
                $drive = strtoupper($matches[1]) . DIRECTORY_SEPARATOR;

                if (is_dir($drive) && is_writable($drive)) {
                    return $drive;
                }
            }
        }

        return null;
    }

    private function downloadsDirectory(): string
    {
        if (PHP_OS_FAMILY === 'Windows') {
            $profile = getenv('USERPROFILE') ?: getenv('HOMEDRIVE') . getenv('HOMEPATH');

            if ($profile && is_dir($profile)) {
                return rtrim($profile, '\\/') . DIRECTORY_SEPARATOR . 'Downloads';
            }
        }

        $home = getenv('HOME');

        if ($home && is_dir($home)) {
            return rtrim($home, '\\/') . DIRECTORY_SEPARATOR . 'Downloads';
        }

        return storage_path('app/backups');
    }

    private function ensureDirectory(string $directory): void
    {
        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException("Unable to create backup directory: {$directory}");
        }

        if (! is_writable($directory)) {
            throw new RuntimeException("Backup directory is not writable: {$directory}");
        }
    }

    private function backupFilename(string $database): string
    {
        return sprintf(
            'fayhos-%s-database-backup-%s.sql',
            Str::slug($database ?: 'database'),
            now()->format('Y-m-d-His')
        );
    }

    private function mysqldumpPath(): string
    {
        $candidates = [
            'C:\\laragon\\bin\\mysql\\mysql-8.4.3-winx64\\bin\\mysqldump.exe',
        ];

        foreach (glob('C:\\laragon\\bin\\mysql\\*\\bin\\mysqldump.exe') ?: [] as $candidate) {
            $candidates[] = $candidate;
        }

        foreach ($candidates as $candidate) {
            if (is_file($candidate)) {
                return $candidate;
            }
        }

        return 'mysqldump';
    }

    private function absolutePath(string $path, string $basePath): string
    {
        if (preg_match('/^[A-Z]:[\\\\\\/]/i', $path) || str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return rtrim($basePath, '\\/') . DIRECTORY_SEPARATOR . $path;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);

        return round($bytes / (1024 ** $power), 2) . ' ' . $units[$power];
    }
}
