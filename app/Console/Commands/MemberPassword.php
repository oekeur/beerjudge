<?php

namespace App\Console\Commands;

use App\Models\Member;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('member:password {email? : Member email address}')]
#[Description('Reset a member\'s password')]
class MemberPassword extends Command
{
    public function handle(): int
    {
        $emailArg = $this->argument('email');
        $emailAsked = $this->ask('Email');
        $email = is_string($emailArg) ? $emailArg : (is_string($emailAsked) ? $emailAsked : '');

        $member = Member::where('email', $email)->first();

        if (! $member) {
            $this->error("No member found with email: {$email}");

            return self::FAILURE;
        }

        $password = $this->secret('New password');

        if (! $password) {
            $this->error('Password cannot be empty.');

            return self::FAILURE;
        }

        $confirm = $this->secret('Confirm password');

        if ($password !== $confirm) {
            $this->error('Passwords do not match.');

            return self::FAILURE;
        }

        $member->update(['password' => $password]);

        $this->info("Password updated for {$member->name} ({$member->email}).");

        return self::SUCCESS;
    }
}
