<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    /**
     * Show login form on the homepage /
     */
    public function showLogin()
    {
        if (Auth::check()) {
            return redirect()->route('admin.dashboard');
        }

        return view('auth.login');
    }

    /**
     * Handle login authentication by username or email with rate limiting
     */
    public function login(Request $request)
    {
        $request->validate([
            'login' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $throttleKey = Str::transliterate(Str::lower($request->input('login')) . '|' . $request->ip());

        // Max 5 failed attempts per minute
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            return back()->withErrors([
                'login' => "Terlalu banyak percobaan masuk yang salah. Silakan coba kembali dalam {$seconds} detik.",
            ])->onlyInput('login');
        }

        $login = $request->input('login');
        $password = $request->input('password');

        $user = User::where('name', $login)->orWhere('email', $login)->first();

        if ($user && Hash::check($password, $user->password)) {
            RateLimiter::clear($throttleKey);
            Auth::login($user, $request->boolean('remember'));
            $request->session()->regenerate();
            return redirect()->intended(route('admin.dashboard'));
        }

        RateLimiter::hit($throttleKey, 60);

        return back()->withErrors([
            'login' => 'Username/email atau password yang Anda masukkan tidak sesuai.',
        ])->onlyInput('login');
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home')->with('success', 'Anda telah berhasil keluar.');
    }
}

