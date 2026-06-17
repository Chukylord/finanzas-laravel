<?php

namespace App\Http\Controllers;

use App\Services\DatabaseBackupService;
use RuntimeException;

class BackupController extends Controller
{
    public function index(DatabaseBackupService $backups)
    {
        return view('backups.index', [
            'backups' => $backups->list(),
            'backupDirectory' => $backups->backupDirectory(),
        ]);
    }

    public function store(DatabaseBackupService $backups)
    {
        try {
            $backup = $backups->create();
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('backups.index')
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('backups.index')
            ->with('ok', 'Backup generado: ' . $backup['name']);
    }

    public function download(string $filename, DatabaseBackupService $backups)
    {
        try {
            $path = $backups->resolveBackupPath($filename);
        } catch (RuntimeException) {
            abort(404);
        }

        return response()->download($path, $filename, [
            'Content-Type' => 'application/sql',
        ]);
    }

    public function destroy(string $filename, DatabaseBackupService $backups)
    {
        try {
            $backups->delete($filename);
        } catch (RuntimeException $exception) {
            return redirect()
                ->route('backups.index')
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('backups.index')
            ->with('ok', 'Backup eliminado: ' . $filename);
    }
}
