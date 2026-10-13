<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Support\PasswordRules;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PasswordResetController extends Controller
{
    public function showRequestForm(): View
    {
        return view('auth.password-reset-request');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email']]);

        $email = Member::normalizeEmail($request->string('email')->toString());

        $member = Member::findByEmail($email);

        if ($member !== null && $member->is_active) {
            Password::broker()->sendResetLink(['email' => $email]);
        }

        return back()->with('success', __('passwords.sent'));
    }

    public function showResetForm(Request $request, string $token): View
    {
        $emailValue = $request->query('email');
        $email = is_string($emailValue) ? $emailValue : null;

        return view('auth.password-reset', [
            'token' => $token,
            'email' => $email,
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => PasswordRules::required(),
        ]);

        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (Member $member, string $password): void {
                $member->password = $password;
                $member->setRememberToken(Str::random(60));
                $member->save();

                event(new PasswordReset($member));
            }
        );

        if ($status === Password::PasswordReset) {
            Auth::login(Member::query()->where('email', Member::normalizeEmail($request->string('email')->toString()))->firstOrFail());

            return redirect('/');
        }

        return back()->withErrors(['email' => is_string($status) ? __($status) : ''])->onlyInput('email');
    }
}
