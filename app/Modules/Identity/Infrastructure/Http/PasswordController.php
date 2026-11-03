<?php

namespace App\Modules\Identity\Infrastructure\Http;

use App\Http\Controllers\Controller;
use App\Shared\Audit\Auditor;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;
use Inertia\Inertia;
use Inertia\Response;

class PasswordController extends Controller
{
    public function __construct(private readonly Auditor $auditor) {}

    public function forgot(): Response
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    public function sendLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);
        Password::sendResetLink($request->only('email'));

        // Respuesta idéntica exista o no la cuenta, para no revelar usuarios.
        return back()->with('success', 'Si el correo existe, recibirás un enlace para restablecer tu contraseña.');
    }

    public function reset(Request $request, string $token): Response
    {
        return Inertia::render('Auth/ResetPassword', ['token' => $token, 'email' => $request->query('email')]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)],
        ]);

        $status = Password::reset($data, function ($user, $password) {
            $user->forceFill(['password' => Hash::make($password), 'remember_token' => Str::random(60), 'must_change_password' => false])->save();
            $this->auditor->log('auth.password_reset', $user->id);
            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withErrors(['email' => 'El enlace no es válido o ha vencido.']);
        }

        return redirect('/login')->with('success', 'Contraseña actualizada. Ya puedes iniciar sesión.');
    }

    public function change(): Response
    {
        return Inertia::render('Auth/ChangePassword');
    }

    public function updateOwn(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', PasswordRule::min(8)],
        ]);

        $user = Auth::user();
        $user->forceFill(['password' => Hash::make($data['password']), 'must_change_password' => false])->save();
        $this->auditor->log('auth.password_changed', $user->id);

        return redirect($user->can('study') ? '/my/courses' : '/dashboard')->with('success', 'Contraseña actualizada.');
    }
}
