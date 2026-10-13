<?php

namespace App\Console\Commands;

use App\Models\Member;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('member:delete {email? : Member email address} {--deactivate : Deactivate instead of delete}')]
#[Description('Delete or deactivate a member')]
class MemberDelete extends Command
{
    public function handle(): int
    {
        $emailArg = $this->argument('email');
        $emailAsked = $this->ask('Email');
        $email = is_string($emailArg) ? $emailArg : (is_string($emailAsked) ? $emailAsked : '');

        $member = Member::findByEmail($email);

        if (! $member) {
            $this->error("No member found with email: {$email}");

            return self::FAILURE;
        }

        $this->line("Found: {$member->name} ({$member->email})".($member->is_admin ? ' [admin]' : ''));

        if ($member->isLastActiveAdmin()) {
            $this->error('Cannot deactivate or delete the last active admin.');

            return self::FAILURE;
        }

        if ($this->option('deactivate')) {
            $member->update(['is_active' => false]);
            $this->info('Member deactivated.');

            return self::SUCCESS;
        }

        $action = $this->choice(
            'What do you want to do?',
            ['Deactivate (keeps data, blocks login)', 'Delete permanently'],
            0,
        );

        if (is_string($action) && str_starts_with($action, 'Deactivate')) {
            $member->update(['is_active' => false]);
            $this->info('Member deactivated.');

            return self::SUCCESS;
        }

        if (! $this->confirm("Permanently delete {$member->name}? This cannot be undone.", false)) {
            $this->line('Aborted.');

            return self::SUCCESS;
        }

        $member->delete();
        $this->info('Member deleted.');

        return self::SUCCESS;
    }
}
