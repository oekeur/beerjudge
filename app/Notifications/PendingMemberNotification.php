<?php

namespace App\Notifications;

use App\Filament\Resources\Members\MemberResource;
use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells the admin notification address that an invite registration awaits approval.
 */
class PendingMemberNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Member $member) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('member.pending_mail_subject'))
            ->line(__('member.pending_mail_intro', ['name' => $this->member->name, 'email' => $this->member->email]))
            ->action(__('member.pending_mail_action'), self::pendingMembersUrl());
    }

    /**
     * The Members list with the pending-approval filter on. Filament binds table filters
     * to the `filters` query parameter; the panel is named because a queue worker has no
     * current panel.
     */
    public static function pendingMembersUrl(): string
    {
        return MemberResource::getUrl('index', [
            'filters' => ['pending_approval' => ['isActive' => true]],
        ], panel: 'admin');
    }
}
