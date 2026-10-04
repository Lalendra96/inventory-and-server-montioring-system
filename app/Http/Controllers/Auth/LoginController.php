<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(Request $request, AuditService $audit): RedirectResponse
    {
        $credentials = $request->validate([
            'login' => ['required', 'string', 'max:190'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $field = filter_var($credentials['login'], FILTER_VALIDATE_EMAIL) ? 'email' : 'username';
        $remember = $request->boolean('remember');

        if (! Auth::attempt([$field => $credentials['login'], 'password' => $credentials['password'], 'is_active' => true], $remember)) {
            $audit->record('AUTH_LOGIN_FAILED', null, [], ['login' => $credentials['login']]);
            return back()->withErrors(['login' => 'The supplied credentials are invalid or the account is disabled.'])->onlyInput('login');
        }

        $request->session()->regenerate();
        $request->user()->forceFill(['last_login_at' => now()])->save();
        $audit->record('AUTH_LOGIN_SUCCESS', $request->user());

        return redirect()->intended(route('dashboard'));
    }

    public function destroy(Request $request, AuditService $audit): RedirectResponse
    {
        if ($request->user()) {
            $audit->record('AUTH_LOGOUT', $request->user());
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
