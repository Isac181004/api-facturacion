<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PortalController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('portal.home');
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::attempt($credentials + ['active' => true])) {
            return back()->withErrors(['email' => 'El correo o la contraseña no son correctos, o la cuenta está inactiva.'])
                ->onlyInput('email');
        }

        $request->session()->regenerate();
        $user = $request->user();

        if ($user->isLocked()) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors(['email' => 'La cuenta está temporalmente bloqueada.'])->onlyInput('email');
        }

        return redirect()->intended(route('portal.home'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('status', 'Sesión cerrada.');
    }

    public function home(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasRole('super_admin')) {
            return redirect()->route('portal.admin.companies');
        }

        abort_unless($user->company_id, 403, 'Tu usuario aún no está vinculado a una empresa.');

        return redirect()->route('portal.company.settings');
    }

    public function companies(): View
    {
        abort_unless(request()->user()?->hasRole('super_admin'), 403);

        $companies = Company::query()
            ->withCount(['branches', 'apiKeys'])
            ->orderBy('razon_social')
            ->get();

        return view('admin.companies.index', compact('companies'));
    }
}
