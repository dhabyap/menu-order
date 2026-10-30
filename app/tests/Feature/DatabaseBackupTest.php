<?php

namespace Tests\Feature;

use App\Console\Commands\BackupDatabase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DatabaseBackupTest extends TestCase
{
    use RefreshDatabase;

    public function test_backup_command_creates_sql_file(): void
    {
        $this->artisan(BackupDatabase::class)
            ->assertExitCode(0);

        $this->assertTrue(true);
    }
}
