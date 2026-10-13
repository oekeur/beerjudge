<?php

namespace App\Console\Commands;

use App\Models\Member;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

#[Signature('member:create {--name=} {--email=} {--admin : Grant admin access}')]
#[Description('Create a new member')]
class MemberCreate extends Command
{
    public function handle(): int
    {
        $nameOpt = $this->option('name');
        $nameAsked = $this->ask('Name');
        $name = is_string($nameOpt) ? $nameOpt : (is_string($nameAsked) ? $nameAsked : '');
        $emailOpt = $this->option('email');
        $emailAsked = $this->ask('Email');
        $email = Member::normalizeEmail(is_string($emailOpt) ? $emailOpt : (is_string($emailAsked) ? $emailAsked : ''));

        $validator = Validator::make(
            ['name' => $name, 'email' => $email],
            ['name' => 'required', 'email' => 'required|email|unique:members,email'],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $password = $this->secret('Password');

        if (! $password) {
            $this->error('Password cannot be empty.');

            return self::FAILURE;
        }

        $isAdmin = $this->option('admin') || $this->confirm('Grant admin access?', false);

        $member = Member::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'is_admin' => $isAdmin,
            'is_active' => true,
        ]);

        $this->info("Member created: {$member->name} ({$member->email})".($member->is_admin ? ' [admin]' : '').'.');

        return self::SUCCESS;
    }
}
