<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AuthController extends Controller
{
    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request)
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:100'],
            'phone' => ['required', 'string', 'max:20', 'unique:users,phone', 'regex:/^[0-9+ -]+$/'],
            'password' => ['required', 'string', Password::min(6)],
            'gender' => ['required', 'in:lelaki,perempuan'],
            'race' => ['required', 'in:melayu,cina,india'],
            'age_confirmed' => ['accepted'],
        ], [
            'age_confirmed.accepted' => 'Anda mesti sahkan umur 18 tahun ke atas untuk daftar.',
            'phone.unique' => 'Nombor telefon ini sudah didaftarkan.',
        ]);

        $user = User::create([
            'full_name' => $validated['full_name'],
            'phone' => $validated['phone'],
            'password' => Hash::make($validated['password']),
            'gender' => $validated['gender'],
            'race' => $validated['race'],
            'age_confirmed' => true,
            'credits' => 5,
            'last_free_topup_month' => now()->format('Y-m'),
        ]);

        Auth::guard('web')->login($user);

        return redirect()->route('dashboard')->with('status', 'Pendaftaran berjaya! Selamat mencari jodoh, '.$user->full_name.' 💕');
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'phone' => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::guard('web')->attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return redirect()->intended(route('dashboard'));
        }

        return back()->withErrors([
            'phone' => 'Nombor telefon atau kata laluan salah.',
        ])->onlyInput('phone');
    }

    public function logout(Request $request)
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
