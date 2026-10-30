<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    public function index()
    {
        $backupDir = storage_path('app/backups');
        $files = [];

        if (is_dir($backupDir)) {
            foreach (glob($backupDir.'/*.sql') as $path) {
                $files[] = [
                    'name' => basename($path),
                    'size' => File::size($path),
                    'modified' => date('Y-m-d H:i', filemtime($path)),
                ];
            }
        }

        return response()->json(['backups' => array_reverse($files)]);
    }

    public function download(string $filename): StreamedResponse
    {
        $path = storage_path('app/backups/'.$filename);

        abort_if(!File::exists($path), 404);

        return response()->streamDownload(function () use ($path) {
            echo File::get($path);
        }, $filename);
    }
}
