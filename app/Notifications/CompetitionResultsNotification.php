<?php

namespace App\Notifications;

use App\Models\Competition;
use App\Models\Member;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CompetitionResultsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Competition $competition) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        /** @var Member $notifiable */
        return (new MailMessage)
            ->subject(__('competition.results_mail_subject', ['competition' => $this->competition->name]))
            ->line(__('competition.results_mail_intro', ['competition' => $this->competition->name]))
            ->action(__('competition.results_mail_action'), route('results.show', [$this->competition, $notifiable]));
    }
}
