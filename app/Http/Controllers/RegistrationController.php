<?php

namespace App\Http\Controllers;

use App\Http\Middleware\SetLocale;
use App\Http\Requests\RegisterMemberRequest;
use App\Models\Invitation;
use App\Models\Member;
use App\Notifications\PendingMemberNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;
use Throwable;

class RegistrationController extends Controller
{
    public function show(string $token, Request $request): View|Response|RedirectResponse
    {
        if ($request->user() !== null) {
            return redirect()->route('home')->with('success', __('auth.invite_already_logged_in'));
        }

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

        $member = Member::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => $request->validated('password'),
            'is_active' => false,
            'locale' => 'en',
        ]);

        if ($invitation->type === Invitation::TYPE_SINGLE_USE) {
            $invitation->markUsed();
        }

        $this->notifyAdminOfPendingMember($member);

        return redirect()->route('login')->with('success', __('auth.register_success'));
    }

    /**
     * Mails the admin notification address, in the locale of the member who owns it, else
     * the configured default; never the registrant's browser language. A failed dispatch
     * is reported and does not fail the registration, which has already been saved.
     */
    private function notifyAdminOfPendingMember(Member $member): void
    {
        $address = config('app.admin_notification_email');

        if (! is_string($address) || trim($address) === '') {
            return;
        }

        try {
            $owner = Member::where('email', Member::normalizeEmail($address))->first();

            Notification::route('mail', $address)
                ->notify((new PendingMemberNotification($member))->locale($owner?->locale ?: SetLocale::defaultLocale()));
        } catch (Throwable $exception) {
            report($exception);
        }
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
