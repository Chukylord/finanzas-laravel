<?php

namespace App\Services;

use Carbon\Carbon;
use RuntimeException;
use Symfony\Component\Process\Process;
use Throwable;

class DatabaseBackupService
{
    private const FILENAME_PATTERN = '/\Abackup_finanzas_\d{4}_\d{2}_\d{2}_\d{6}\.sql\z/';

    public function create(): array
    {
        $connectionName = (string) config('database.default');
        $connection = config("database.connections.{$connectionName}");

        if (! is_array($connection)) {
            throw new RuntimeException('No se encontro la configuracion de la conexion de base de datos.');
        }

        $driver = (string) ($connection['driver'] ?? '');
        if (! in_array($driver, ['mysql', 'mariadb'], true)) {
            throw new RuntimeException('El backup automatico solo esta disponible para conexiones MySQL/MariaDB.');
        }

        $database = (string) ($connection['database'] ?? '');
        if ($database === '') {
            throw new RuntimeException('No se encontro el nombre de la base de datos para generar el backup.');
        }

        $this->ensureBackupDirectoryExists();

        $filename = 'backup_finanzas_' . now()->format('Y_m_d_His') . '.sql';
        $path = $this->backupDirectory() . DIRECTORY_SEPARATOR . $filename;
        $mysqldump = $this->resolveMysqldumpPath();
        $host = $this->normalizeHost((string) ($connection['host'] ?? '127.0.0.1'));
        $port = $this->normalizePort($connection['port'] ?? '3306');
        $charset = (string) ($connection['charset'] ?? 'utf8mb4');
        $defaultsFile = $this->createDefaultsFile($connection, $host, $port);
        $password = (string) ($connection['password'] ?? '');

        try {
            $process = new Process([
                $mysqldump,
                '--defaults-extra-file=' . $defaultsFile,
                '--protocol=TCP',
                '--host=' . $host,
                '--port=' . $port,
                '--single-transaction',
                '--routines',
                '--triggers',
                '--add-drop-table',
                '--default-character-set=' . $charset,
                '--result-file=' . $path,
                $database,
            ], base_path(), $this->windowsSafeEnvironment());

            $process->setTimeout(300);
            $process->run();

            if (! $process->isSuccessful()) {
                @unlink($path);
                $output = trim($process->getErrorOutput() ?: $process->getOutput());
                throw new RuntimeException(
                    'No se pudo generar el backup. ' .
                    $this->backupContext($mysqldump, $host, $port, $database) .
                    ' Detalle: ' . $this->sanitizeOutput($output, $password)
                );
            }

            if (! is_file($path) || filesize($path) === 0) {
                @unlink($path);
                throw new RuntimeException(
                    'El backup se genero vacio. ' .
                    $this->backupContext($mysqldump, $host, $port, $database) .
                    ' Verifica permisos y conexion de base de datos.'
                );
            }

            return $this->formatBackup($path);
        } finally {
            @unlink($defaultsFile);
        }
    }

    public function list(): array
    {
        $this->ensureBackupDirectoryExists();

        $files = glob($this->backupDirectory() . DIRECTORY_SEPARATOR . 'backup_finanzas_*.sql') ?: [];

        $files = array_filter($files, function (string $path): bool {
            return is_file($path) && preg_match(self::FILENAME_PATTERN, basename($path)) === 1;
        });

        usort($files, fn (string $a, string $b): int => filemtime($b) <=> filemtime($a));

        return array_map(fn (string $path): array => $this->formatBackup($path), $files);
    }

    public function resolveBackupPath(string $filename): string
    {
        if (! $this->isValidFilename($filename)) {
            throw new RuntimeException('Nombre de backup invalido.');
        }

        $this->ensureBackupDirectoryExists();

        $directory = realpath($this->backupDirectory());
        $path = $directory . DIRECTORY_SEPARATOR . $filename;
        $realPath = realpath($path);

        if (! $directory || ! $realPath || ! is_file($realPath)) {
            throw new RuntimeException('Backup no encontrado.');
        }

        $directory = rtrim($directory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (! str_starts_with($realPath, $directory)) {
            throw new RuntimeException('Ruta de backup invalida.');
        }

        return $realPath;
    }

    public function delete(string $filename): void
    {
        $path = $this->resolveBackupPath($filename);

        if (! @unlink($path)) {
            throw new RuntimeException('No se pudo eliminar el backup seleccionado.');
        }
    }

    public function backupDirectory(): string
    {
        return storage_path('app/backups');
    }

    public function isValidFilename(string $filename): bool
    {
        return preg_match(self::FILENAME_PATTERN, $filename) === 1;
    }

    private function ensureBackupDirectoryExists(): void
    {
        $directory = $this->backupDirectory();

        if (is_dir($directory)) {
            return;
        }

        if (! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('No se pudo crear la carpeta de backups.');
        }
    }

    private function ensureTempDirectoryExists(): void
    {
        $directory = $this->tempDirectory();

        if (is_dir($directory)) {
            return;
        }

        if (! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException('No se pudo crear la carpeta temporal de backups.');
        }
    }

    private function resolveMysqldumpPath(): string
    {
        $candidates = [
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'mysqldump',
        ];

        foreach ($candidates as $candidate) {
            if ($candidate !== 'mysqldump' && ! is_file($candidate)) {
                continue;
            }

            try {
                $process = new Process([$candidate, '--version'], base_path(), $this->windowsSafeEnvironment());
                $process->setTimeout(10);
                $process->run();

                if ($process->isSuccessful()) {
                    return $candidate;
                }
            } catch (Throwable) {
                continue;
            }
        }

        throw new RuntimeException('No se encontro mysqldump. Se intento C:\\xampp\\mysql\\bin\\mysqldump.exe y mysqldump del PATH. Agrega C:\\xampp\\mysql\\bin al PATH o verifica que exista mysqldump.exe.');
    }

    private function createDefaultsFile(array $connection, string $host, string $port): string
    {
        $this->ensureTempDirectoryExists();

        $path = tempnam($this->tempDirectory(), 'mysqldump_');

        if (! $path) {
            throw new RuntimeException('No se pudo crear el archivo temporal para mysqldump.');
        }

        $lines = ['[client]'];

        $this->appendOptionLine($lines, 'user', $connection['username'] ?? null);
        $this->appendOptionLine($lines, 'password', $connection['password'] ?? null, skipEmpty: true);
        $this->appendOptionLine($lines, 'host', $host);
        $this->appendOptionLine($lines, 'port', $port);
        $this->appendOptionLine($lines, 'protocol', 'TCP');
        $this->appendOptionLine($lines, 'default-character-set', $connection['charset'] ?? 'utf8mb4');

        file_put_contents($path, implode(PHP_EOL, $lines) . PHP_EOL);
        @chmod($path, 0600);

        return $path;
    }

    private function tempDirectory(): string
    {
        return storage_path('app/temp');
    }

    private function normalizeHost(string $host): string
    {
        $host = trim($host);

        if ($host === '' || strtolower($host) === 'localhost') {
            return '127.0.0.1';
        }

        return $host;
    }

    private function normalizePort(mixed $port): string
    {
        $port = trim((string) $port);

        return $port !== '' ? $port : '3306';
    }

    private function windowsSafeEnvironment(): array
    {
        $environment = getenv();
        $environment = is_array($environment) ? $environment : [];

        foreach ($_SERVER as $key => $value) {
            if (is_scalar($value) && ! array_key_exists($key, $environment)) {
                $environment[$key] = (string) $value;
            }
        }

        $path = $environment['PATH'] ?? $environment['Path'] ?? null;
        if (! $path) {
            $path = getenv('PATH') ?: getenv('Path') ?: null;
        }

        if ($path) {
            $environment['PATH'] = (string) $path;
            $environment['Path'] = (string) $path;
        }

        $windowsDirectory = getenv('SystemRoot') ?: getenv('WINDIR') ?: 'C:\\Windows';
        $tempDirectory = getenv('TEMP') ?: getenv('TMP') ?: sys_get_temp_dir();

        $environment['SystemRoot'] = (string) ($environment['SystemRoot'] ?? $windowsDirectory);
        $environment['WINDIR'] = (string) ($environment['WINDIR'] ?? $windowsDirectory);
        $environment['TEMP'] = (string) ($environment['TEMP'] ?? $tempDirectory);
        $environment['TMP'] = (string) ($environment['TMP'] ?? $tempDirectory);

        $cleanEnvironment = [];
        foreach ($environment as $key => $value) {
            if ($value === null || is_array($value) || is_object($value)) {
                continue;
            }

            $cleanEnvironment[$key] = (string) $value;
        }

        return $cleanEnvironment;
    }

    private function backupContext(string $mysqldump, string $host, string $port, string $database): string
    {
        return 'mysqldump usado: ' . $mysqldump .
            '; host usado: ' . $host .
            '; puerto usado: ' . $port .
            '; database usado: ' . $database . '.';
    }

    private function appendOptionLine(array &$lines, string $key, mixed $value, bool $skipEmpty = false): void
    {
        if ($value === null || ($skipEmpty && (string) $value === '')) {
            return;
        }

        $lines[] = $key . '="' . $this->escapeOptionValue((string) $value) . '"';
    }

    private function escapeOptionValue(string $value): string
    {
        return str_replace(
            ["\\", "\"", "\r", "\n"],
            ["\\\\", "\\\"", '', ''],
            $value
        );
    }

    private function sanitizeOutput(string $output, string $password): string
    {
        if ($password !== '') {
            $output = str_replace($password, '******', $output);
        }

        return $output !== '' ? $output : 'mysqldump no devolvio detalles del error.';
    }

    private function formatBackup(string $path): array
    {
        $size = filesize($path) ?: 0;

        return [
            'name' => basename($path),
            'modified_at' => Carbon::createFromTimestamp(filemtime($path)),
            'size' => $size,
            'size_label' => $this->formatBytes($size),
        ];
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1024 * 1024) {
            return number_format($bytes / 1024 / 1024, 2, ',', '.') . ' MB';
        }

        if ($bytes >= 1024) {
            return number_format($bytes / 1024, 2, ',', '.') . ' KB';
        }

        return $bytes . ' B';
    }
}
