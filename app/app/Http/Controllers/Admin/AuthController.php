<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminAuthService;
use App\Services\EventLogger;
use Illuminate\Http\Request;

/**
 * Handles admin sign in and sign out.
 */
class AuthController extends Controller
{
    public function __construct(private AdminAuthService $adminAuth) {}

    /**
     * Show the admin login form.
     */
    public function showLoginForm()
    {
        return auth()->check() ? redirect('/admin') : view('login');
    }

    /**
     * Attempt to sign the admin in.
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if ($this->adminAuth->attempt($credentials['email'], $credentials['password'], $request->boolean('remember'))) {
            $request->session()->regenerate();

            EventLogger::success('admin.login.success', [
                'email' => $request->user()?->email,
            ]);

            return redirect()->intended('/admin');
        }

        EventLogger::rejected('admin.login.failed', [
            'email_attempted' => $credentials['email'],
        ]);

        return back()
            ->withErrors(['email' => 'Email atau password salah.'])
            ->onlyInput('email');
    }

    /**
     * Sign the admin out and clear the session.
     */
    public function logout(Request $request)
    {
        $email = $request->user()?->email;
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        EventLogger::success('admin.logout', ['email' => $email]);

        return redirect('/admin/login');
    }
}
