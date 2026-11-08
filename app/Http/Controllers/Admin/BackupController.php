<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\DatabaseBackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use RuntimeException;

class BackupController extends Controller
{
    public function index(DatabaseBackupService $backupService): View
    {
        $databaseSize = $backupService->databaseSize();

        return view('admin.backup.index', compact('databaseSize'));
    }

    public function store(DatabaseBackupService $backupService): RedirectResponse
    {
        try {
            $backup = $backupService->backup();
        } catch (RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', "Backup saved to {$backup['target']}: {$backup['filename']} ({$backup['size']})");
    }
}
