<?php

namespace Tests\Feature;

use App\Models\Member;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class MemberDeleteCommandTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_deactivate_option_refuses_last_active_admin(): void
    {
        $admin = Member::factory()->admin()->create();

        $this->artisan('member:delete', ['email' => $admin->email, '--deactivate' => true])
            ->expectsQuestion('Email', $admin->email)
            ->expectsOutputToContain('Cannot deactivate or delete the last active admin.')
            ->assertExitCode(1);

        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_interactive_run_refuses_last_active_admin_before_asking_what_to_do(): void
    {
        $admin = Member::factory()->admin()->create();

        // No expectsChoice: the guard returns first, so an unexpected choice prompt would fail the test.
        $this->artisan('member:delete', ['email' => $admin->email])
            ->expectsQuestion('Email', $admin->email)
            ->expectsOutputToContain('Cannot deactivate or delete the last active admin.')
            ->assertExitCode(1);

        $this->assertModelExists($admin);
        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_active_admin_can_be_deactivated_when_another_admin_exists(): void
    {
        $admin = Member::factory()->admin()->create();
        Member::factory()->admin()->create();

        $this->artisan('member:delete', ['email' => $admin->email, '--deactivate' => true])
            ->expectsQuestion('Email', $admin->email)
            ->assertExitCode(0);

        $this->assertFalse($admin->fresh()->is_active);
    }

    public function test_inactive_admin_can_be_deleted(): void
    {
        Member::factory()->admin()->create();
        $inactive = Member::factory()->admin()->inactive()->create();

        $this->artisan('member:delete', ['email' => $inactive->email])
            ->expectsQuestion('Email', $inactive->email)
            ->expectsChoice('What do you want to do?', 'Delete permanently', ['Deactivate (keeps data, blocks login)', 'Delete permanently'])
            ->expectsConfirmation("Permanently delete {$inactive->name}? This cannot be undone.", 'yes')
            ->assertExitCode(0);

        $this->assertModelMissing($inactive);
    }
}
