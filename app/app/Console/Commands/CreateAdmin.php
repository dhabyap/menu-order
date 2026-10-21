<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class CreateAdmin extends Command
{
    protected $signature = 'admin:create {email} {name}';

    protected $description = 'Create or update an administrator account';

    public function handle(): int
    {
        $password = $this->secret('Password (minimum 12 characters)');
        if (strlen($password) < 12) {
            $this->error('Password must contain at least 12 characters.');

            return self::FAILURE;
        }
        User::updateOrCreate(['email' => $this->argument('email')], ['name' => $this->argument('name'), 'password' => Hash::make($password)]);
        $this->info('Administrator account saved.');

        return self::SUCCESS;
    }
}
