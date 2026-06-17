<?php

namespace App\Console\Commands;

use App\Services\DatabaseBackupService;
use Illuminate\Console\Command;
use RuntimeException;

class BackupDatabaseCommand extends Command
{
    protected $signature = 'app:backup-database';

    protected $description = 'Genera un backup SQL de la base de datos.';

    public function handle(DatabaseBackupService $backups): int
    {
        try {
            $backup = $backups->create();
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Backup generado correctamente.');
        $this->line('Archivo: ' . $backup['name']);
        $this->line('Carpeta: ' . $backups->backupDirectory());
        $this->line('Tamano: ' . $backup['size_label']);

        return self::SUCCESS;
    }
}
