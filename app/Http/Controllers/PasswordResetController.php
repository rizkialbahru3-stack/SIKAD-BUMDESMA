<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    public function create()
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request)
    {
        $request->validate(['email' => ['required', 'email']]);

        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('success', 'Link reset password telah dikirim. Silakan cek email Anda (berlaku 60 menit).');
        }

        return back()->withErrors(['email' => $this->message($status)])->onlyInput('email');
    }

    public function edit(string $token)
    {
        return view('auth.reset-password', ['token' => $token]);
    }

    public function update(Request $request)
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'password.confirmed' => 'Konfirmasi password baru tidak sama.',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user) use ($request) {
                $user->forceFill([
                    'password' => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('success', 'Password berhasil diubah. Silakan login dengan password baru.');
        }

        return back()->withErrors(['email' => $this->message($status)])->onlyInput('email');
    }

    private function message(string $status): string
    {
        return match ($status) {
            Password::INVALID_USER => 'Email tidak terdaftar di sistem kami.',
            Password::INVALID_TOKEN => 'Token reset tidak valid atau sudah kedaluwarsa. Silakan minta link baru.',
            Password::RESET_THROTTLED => 'Terlalu banyak permintaan. Silakan tunggu sebentar lalu coba lagi.',
            default => 'Terjadi kesalahan. Silakan coba lagi.',
        };
    }
}
