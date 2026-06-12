<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\MagicLink;
use App\Models\Member;
use App\Notifications\MagicLinkNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->validated();

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            return $this->member($request)->is_admin
                ? redirect()->intended('/admin')
                : redirect()->intended('/');
        }

        return back()->withErrors(['email' => __('Invalid credentials.')])->onlyInput('email');
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login');
    }

    public function showMagicLinkForm(): View
    {
        return view('auth.magic-link-request');
    }

    public function sendMagicLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $member = Member::where('email', $request->input('email'))->first();

        if ($member !== null) {
            $plain = MagicLink::createForMember($member);
            $url = route('auth.magic-link.verify', ['token' => $plain]);
            $member->notify(new MagicLinkNotification($url));
        }

        return back()->with('success', __('auth.magic_link_sent'));
    }

    public function verifyMagicLink(string $token, Request $request): RedirectResponse
    {
        $magicLink = MagicLink::findByPlaintext($token);

        if ($magicLink === null || ! $magicLink->isValid()) {
            return redirect()->route('login')->withErrors(['email' => __('auth.magic_link_invalid')]);
        }

        $magicLink->markUsed();

        $member = $magicLink->member;

        abort_if($member === null, 401);

        Auth::login($member);
        $request->session()->regenerate();

        return $member->is_admin
            ? redirect()->intended('/admin')
            : redirect()->intended('/');
    }
}
