<?php

namespace Tests\Feature;

use App\Models\Invitation;
use App\Models\MagicLink;
use App\Models\Member;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class HasTokenExpiryTest extends TestCase
{
    use LazilyRefreshDatabase;

    private function makeInvitation(array $overrides = []): Invitation
    {
        return Invitation::create(array_merge([
            'token' => str_repeat('a', 64),
            'type' => Invitation::TYPE_SINGLE_USE,
            'expires_at' => null,
            'used_at' => null,
            'created_by' => Member::factory()->create()->id,
        ], $overrides));
    }

    public function test_valid_when_not_used_and_not_expired(): void
    {
        $invitation = $this->makeInvitation();
        $this->assertTrue($invitation->isValid());
    }

    public function test_invalid_when_used(): void
    {
        $invitation = $this->makeInvitation(['used_at' => now()->subMinute()]);
        $this->assertFalse($invitation->isValid());
    }

    public function test_invalid_when_expired(): void
    {
        $invitation = $this->makeInvitation(['expires_at' => now()->subMinute()]);
        $this->assertFalse($invitation->isValid());
    }

    public function test_valid_when_null_expires_at(): void
    {
        $invitation = $this->makeInvitation(['expires_at' => null]);
        $this->assertTrue($invitation->isValid());
    }

    public function test_invalid_when_used_and_null_expires_at(): void
    {
        $invitation = $this->makeInvitation(['used_at' => now(), 'expires_at' => null]);
        $this->assertFalse($invitation->isValid());
    }

    public function test_invalid_reason_is_null_while_usable(): void
    {
        $this->assertNull($this->makeInvitation()->invalidReason());
    }

    public function test_invalid_reason_reports_expiry(): void
    {
        $invitation = $this->makeInvitation(['expires_at' => now()->subMinute()]);

        $this->assertSame(Invitation::REASON_EXPIRED, $invitation->invalidReason());
    }

    public function test_invalid_reason_prefers_used_over_expired(): void
    {
        $invitation = $this->makeInvitation([
            'used_at' => now(),
            'expires_at' => now()->subMinute(),
        ]);

        $this->assertSame(Invitation::REASON_USED, $invitation->invalidReason());
    }

    public function test_mark_used_sets_used_at(): void
    {
        $invitation = $this->makeInvitation();
        $this->assertNull($invitation->used_at);

        $invitation->markUsed();

        $this->assertNotNull($invitation->fresh()->used_at);
        $this->assertFalse($invitation->fresh()->isValid());
    }

    public function test_magic_link_uses_trait(): void
    {
        $member = Member::factory()->create();

        $link = MagicLink::create([
            'member_id' => $member->id,
            'token' => hash('sha256', 'test'),
            'expires_at' => now()->addMinutes(15),
        ]);

        $this->assertTrue($link->isValid());

        $link->markUsed();
        $this->assertFalse($link->fresh()->isValid());
    }
}
