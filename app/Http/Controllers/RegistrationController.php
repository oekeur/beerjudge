<?php

namespace App\Http\Controllers;

use App\Http\Requests\RegisterMemberRequest;
use App\Models\Invitation;
use App\Models\Member;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function show(string $token): View|Response
    {
        $invitation = $this->findInvitation($token);

        if (! $invitation->isValid()) {
            return $this->invalidInvitation($invitation);
        }

        return view('auth.register', compact('token'));
    }

    public function store(string $token, RegisterMemberRequest $request): RedirectResponse|Response
    {
        $invitation = $this->findInvitation($token);

        if (! $invitation->isValid()) {
            return $this->invalidInvitation($invitation);
        }

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

    private function findInvitation(string $token): Invitation
    {
        return Invitation::where('token', $token)->firstOrFail();
    }

    /**
     * Explains why the link no longer works instead of dead-ending on a bare 410.
     */
    private function invalidInvitation(Invitation $invitation): Response
    {
        return response()->view('auth.invite-invalid', [
            'alreadyUsed' => $invitation->invalidReason() === Invitation::REASON_USED,
            'expiresAt' => $invitation->expires_at,
        ], 410);
    }
}
