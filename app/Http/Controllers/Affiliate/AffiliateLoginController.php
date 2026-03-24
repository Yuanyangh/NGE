<?php

namespace App\Http\Controllers\Affiliate;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AffiliateLoginController extends Controller
{
    public function showLoginForm(string $companySlug)
    {
        $company = Company::whereRaw('LOWER(slug) = ?', [strtolower($companySlug)])->first();

        // Redirect to canonical lowercase URL if case doesn't match
        if ($company && $company->slug !== $companySlug) {
            return redirect()->route('affiliate.login', ['companySlug' => $company->slug]);
        }

        // If company not found, render login view with error (no form)
        if (! $company) {
            return view('affiliate.auth.login', [
                'company' => null,
                'companyError' => 'The company you\'re looking for doesn\'t exist. Please check the URL and try again.',
            ]);
        }

        app()->instance('current_company_id', $company->id);
        app()->instance('current_company', $company);

        return view('affiliate.auth.login', ['company' => $company]);
    }

    public function login(Request $request, string $companySlug)
    {
        $company = Company::whereRaw('LOWER(slug) = ?', [strtolower($companySlug)])->first();

        if (! $company) {
            return back()->withErrors(['company' => 'Company not found.']);
        }

        // Redirect to canonical URL if needed
        if ($company->slug !== $companySlug) {
            return redirect()->route('affiliate.login', ['companySlug' => $company->slug]);
        }

        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Attempt auth first (constant-time password check prevents timing attacks)
        if (! Auth::attempt([
            'email' => $request->input('email'),
            'password' => $request->input('password'),
        ])) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->withInput();
        }

        $user = Auth::user();

        // After successful auth, verify company + role
        if ($user->company_id !== $company->id
            || ! in_array($user->role, ['affiliate', 'admin'], true)) {
            Auth::logout();

            return back()->withErrors(['email' => 'Invalid credentials.'])->withInput();
        }

        $request->session()->regenerate();

        return redirect()->route('affiliate.dashboard', ['company' => $company->slug]);
    }

    public function logout(Request $request)
    {
        $companySlug = Auth::user()?->company?->slug ?? 'default';

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('affiliate.login', ['companySlug' => $companySlug]);
    }
}
