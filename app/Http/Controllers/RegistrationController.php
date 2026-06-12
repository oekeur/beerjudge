<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterMemberRequest;
use App\Models\Invitation;
use App\Models\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function show(string $token): View
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();

        abort_unless($invitation->isValid(), 410);

        return view('auth.register', compact('token'));
    }

    public function store(string $token, RegisterMemberRequest $request): RedirectResponse
    {
        $invitation = Invitation::where('token', $token)->firstOrFail();

        abort_unless($invitation->isValid(), 410);

        Member::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'is_active' => false,
            'locale' => 'en',
        ]);

        if ($invitation->type === Invitation::TYPE_SINGLE_USE) {
            $invitation->markUsed();
        }

        return redirect()->route('login')->with('success', __('auth.register_success'));
    }
}
