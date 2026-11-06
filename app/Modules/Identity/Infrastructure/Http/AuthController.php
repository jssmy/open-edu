<?php

namespace App\Modules\Identity\Infrastructure\Http;

use App\Http\Controllers\Controller;
use App\Shared\Audit\Auditor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AuthController extends Controller
{
    private const MAX_ATTEMPTS = 5;

    public function __construct(private readonly Auditor $auditor) {}

    public function create(): InertiaResponse
    {
        return Inertia::render('Auth/Login');
    }

    public function store(Request $request): RedirectResponse|Response
    {
        $input = $request->validate([
            'login' => ['required_without:email', 'string'],
            'email' => ['sometimes', 'nullable', 'string'],
            'password' => ['required', 'string'],
        ]);

        $loginValue = trim((string) ($input['login'] ?? $input['email']));
        $field = filter_var($loginValue, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $credentials = [$field => $loginValue, 'password' => $input['password']];

        $key = strtolower($loginValue).'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, self::MAX_ATTEMPTS)) {
            $this->auditor->log('auth.throttled', null, null, [$field => $loginValue]);
            abort(429, 'Demasiados intentos. Inténtalo de nuevo en un minuto.');
        }

        if (! Auth::attempt([...$credentials, 'is_active' => true, fn ($query) => $query->whereDoesntHave('roles', fn ($roles) => $roles->where('is_active', false))], $request->boolean('remember'))) {
            RateLimiter::hit($key, 60);
            $this->auditor->log('auth.failed', null, null, [$field => $loginValue]);

            $errorKey = $request->has('email') && ! $request->has('login') ? 'email' : 'login';

            return back()->withErrors([$errorKey => 'Las credenciales no son válidas.'])->onlyInput('login', 'email');
        }

        RateLimiter::clear($key);
        $request->session()->regenerate();
        $this->auditor->log('auth.login', Auth::id());

        if (Auth::user()?->must_change_password) {
            return redirect()->route('password.change');
        }

        return redirect()->intended($this->home());
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->auditor->log('auth.logout', Auth::id());
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    private function home(): string
    {
        return Auth::user()->can('study') ? '/my/courses' : '/dashboard';
    }
}
