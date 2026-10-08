<?php

namespace Tests\Feature;

use App\Filament\Resources\Competitions\Pages\ListCompetitions;
use App\Filament\Resources\Members\Pages\ListMembers;
use App\Models\Competition;
use App\Models\Member;
use Filament\Actions\Testing\TestAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminListPolishTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(Member::factory()->admin()->create());
    }

    public function test_competition_list_shows_year_without_thousands_separator(): void
    {
        Competition::forceCreate([
            'name' => 'Spring Cup',
            'year' => 2024,
            'judging_date' => '2024-04-01',
            'status' => 'draft',
        ]);

        Livewire::test(ListCompetitions::class)
            ->assertSee('2024')
            ->assertDontSee('2,024');
    }

    public function test_invite_type_field_has_its_own_label(): void
    {
        Livewire::test(ListMembers::class)
            ->mountAction(TestAction::make('generate_invite'))
            ->assertSchemaComponentExists('type', 'mountedActionSchema0', fn ($component): bool => $component->getLabel() === __('competition.invite_type')
                && $component->getLabel() !== __('competition.invite_type_single'));
    }
}
