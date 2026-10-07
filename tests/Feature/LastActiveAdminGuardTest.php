<?php

namespace Tests\Feature;

use App\Filament\Resources\Members\Pages\EditMember;
use App\Models\Member;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LastActiveAdminGuardTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_last_active_admin_cannot_be_demoted(): void
    {
        $admin = Member::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(EditMember::class, ['record' => $admin->id])
            ->fillForm(['is_admin' => false])
            ->call('save')
            ->assertHasFormErrors(['is_admin']);

        $this->assertTrue($admin->fresh()->is_admin);
    }

    public function test_last_active_admin_cannot_be_deactivated(): void
    {
        $admin = Member::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(EditMember::class, ['record' => $admin->id])
            ->fillForm(['is_active' => false])
            ->call('save')
            ->assertHasFormErrors(['is_active']);

        $this->assertTrue($admin->fresh()->is_active);
    }

    public function test_last_active_admin_cannot_be_deleted(): void
    {
        $admin = Member::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(EditMember::class, ['record' => $admin->id])
            ->callAction(DeleteAction::class)
            ->assertNotified(__('members.last_admin_delete'));

        $this->assertDatabaseHas('members', ['id' => $admin->id]);
    }

    public function test_admin_can_be_demoted_while_another_active_admin_exists(): void
    {
        $admin = Member::factory()->admin()->create();
        Member::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(EditMember::class, ['record' => $admin->id])
            ->fillForm(['is_admin' => false])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertFalse($admin->fresh()->is_admin);
    }

    public function test_inactive_admin_does_not_count_as_another_active_admin(): void
    {
        $admin = Member::factory()->admin()->create();
        Member::factory()->admin()->inactive()->create();
        $this->actingAs($admin);

        Livewire::test(EditMember::class, ['record' => $admin->id])
            ->fillForm(['is_admin' => false])
            ->call('save')
            ->assertHasFormErrors(['is_admin']);
    }

    public function test_admin_can_be_deleted_while_another_active_admin_exists(): void
    {
        $admin = Member::factory()->admin()->create();
        $other = Member::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(EditMember::class, ['record' => $other->id])
            ->callAction(DeleteAction::class);

        $this->assertDatabaseMissing('members', ['id' => $other->id]);
    }
}
