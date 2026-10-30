<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BackupDatabase extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'backup:database';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a database backup and delete backups older than 7 days';

    public function handle(): int
    {
        $backupDir = storage_path('app/backups');
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0775, true);
        }

        $filename = 'backup-'.now()->format('Y-m-d_His').'.sql';
        $target = $backupDir.'/'.$filename;

        $sql = $this->dumpSql();

        if ($sql === false) {
            $this->error('Gagal membuat backup database.');
            return 1;
        }

        file_put_contents($target, $sql);
        $this->info('Backup dibuat: '.$target);

        $this->purgeOldBackups($backupDir);

        return 0;
    }

    private function dumpSql(): string
    {
        $sql = "-- Backup Racik Tea\n-- Generated: ".now()->toDateTimeString()."\n\n";

        $tables = $this->listTables();

        foreach ($tables as $table) {
            $sql .= $this->dumpTable($table);
        }

        return $sql;
    }

    private function listTables(): array
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $rows = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%'");
            return collect($rows)->map(fn ($row) => $row->name)->all();
        }

        $rows = DB::select('SHOW TABLES');

        return collect($rows)->map(fn ($row) => array_values((array) $row)[0])->all();
    }

    private function dumpTable(string $table): string
    {
        $sql = '';
        $driver = DB::connection()->getDriverName();

        try {
            if ($driver === 'sqlite') {
                $createStatement = DB::selectOne("SELECT sql FROM sqlite_master WHERE type='table' AND name = ?", [$table]);
                if ($createStatement && $createStatement->sql) {
                    $sql .= "-- Table: {$table}\n";
                    $sql .= $createStatement->sql. "\n\n";
                }
            } else {
                $createStatement = DB::selectOne("SHOW CREATE TABLE `{$table}`");
                if ($createStatement) {
                    $sql .= "-- Table: {$table}\n";
                    $sql .= $createStatement->CreateTable. "\n\n";
                }
            }

            $rows = DB::table($table)->get();
            foreach ($rows as $row) {
                $values = collect((array) $row)->map(function ($value) {
                    return $value === null ? 'NULL' : "'".addslashes((string) $value)."'";
                })->implode(', ');

                $sql .= "INSERT INTO {$table} VALUES ({$values});\n";
            }

            $sql .= "\n";
        } catch (\Exception $e) {
            $sql .= "-- Warning: failed to dump table {$table}: ".$e->getMessage()."\n\n";
        }

        return $sql;
    }

    private function purgeOldBackups(string $directory): void
    {
        $files = glob($directory.'/*.sql');
        foreach ($files as $file) {
            $mtime = filemtime($file);
            if ($mtime !== false && $mtime < now()->subDays(7)->getTimestamp()) {
                @unlink($file);
                $this->info('Menghapus backup lama: '.basename($file));
            }
        }
    }
}
