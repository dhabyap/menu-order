<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

/**
 * Authenticates the single store admin against credentials kept in the
 * environment (ADMIN_EMAIL / ADMIN_PASSWORD), not the users table.
 *
 * The users row is only a session anchor so Laravel's "auth" middleware and
 * session guard keep working; it is created automatically on first login.
 */
class AdminAuthService
{
    /**
     * Attempt to authenticate the admin, logging them in on success.
     */
    public function attempt(string $email, string $password, bool $remember = false): bool
    {
        if (! $this->credentialsMatch($email, $password)) {
            return false;
        }

        auth()->login($this->resolveUser(), $remember);

        return true;
    }

    /**
     * Compare the submitted credentials with the environment values.
     *
     * The stored password may be plain text or an already-hashed value; hashed
     * values are verified through the Hash contract, plain text in constant time.
     */
    public function credentialsMatch(string $email, string $password): bool
    {
        $expectedEmail = (string) config('admin.email');
        $expectedPassword = (string) config('admin.password');

        if ($expectedEmail === '' || $expectedPassword === '') {
            return false;
        }

        $passwordMatches = Hash::isHashed($expectedPassword)
            ? Hash::check($password, $expectedPassword)
            : hash_equals($expectedPassword, $password);

        $emailMatches = hash_equals(strtolower($expectedEmail), strtolower($email));

        return $emailMatches && $passwordMatches;
    }

    /**
     * Find or create the admin user row that backs the authenticated session.
     *
     * The canonical email comes from the environment so casing differences in
     * the submitted value never create a duplicate row.
     */
    protected function resolveUser(): User
    {
        $email = (string) config('admin.email');

        return User::firstOrCreate(
            ['email' => $email],
            ['name' => 'Administrator', 'password' => (string) config('admin.password')]
        );
    }
}
