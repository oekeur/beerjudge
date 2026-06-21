<?php

namespace App\Http\Controllers;

use App\Models\Member;
use App\Support\PasswordRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('profile.edit', ['member' => auth()->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var Member $member */
        $member = auth()->user();

        /** @var array{name: string, email: string, locale: string, current_password: string|null, password: string|null} $data */
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:members,email,'.$member->id],
            'locale' => ['required', 'string', 'in:en,nl'],
            'current_password' => ['nullable', 'string'],
            'password' => PasswordRules::optional(),
        ]);

        if ($request->filled('password')) {
            if (! Hash::check($data['current_password'] ?? '', $member->password)) {
                return back()->withErrors(['current_password' => __('profile.wrong_current_password')])->withInput();
            }
        }

        $member->name = $data['name'];
        $member->email = $data['email'];
        $member->locale = $data['locale'];

        if ($request->filled('password') && is_string($data['password'])) {
            $member->password = Hash::make($data['password']);
        }

        $member->save();

        return back()->with('status', __('profile.saved'));
    }
}
