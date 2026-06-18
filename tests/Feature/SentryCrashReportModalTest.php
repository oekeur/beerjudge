<?php

namespace Tests\Feature;

use App\Models\Member;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Sentry\EventId;
use Sentry\SentrySdk;
use Sentry\State\HubInterface;
use Tests\TestCase;

class SentryCrashReportModalTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function tearDown(): void
    {
        // Reset Sentry hub state between tests
        SentrySdk::init();

        parent::tearDown();
    }

    public function test_sentry_script_and_button_present_when_dsn_and_event_id_set(): void
    {
        config(['sentry.dsn' => 'https://test@sentry.io/123']);

        $hub = $this->createMock(HubInterface::class);
        $hub->method('getLastEventId')->willReturn(
            new EventId('aaaabbbbccccddddaaaabbbbccccdddd')
        );
        SentrySdk::setCurrentHub($hub);

        $html = view('errors.500')->render();

        $this->assertStringContainsString('browser.sentry-cdn.com', $html);
        $this->assertStringContainsString('Send error report', $html);
        $this->assertStringContainsString('Back to home', $html);
    }

    public function test_sentry_script_absent_when_dsn_is_empty(): void
    {
        config(['sentry.dsn' => '']);

        $html = view('errors.500')->render();

        $this->assertStringNotContainsString('browser.sentry-cdn.com', $html);
        $this->assertStringNotContainsString('Send error report', $html);
        $this->assertStringContainsString('Back to home', $html);
    }

    public function test_sentry_script_absent_when_dsn_set_but_event_id_is_null(): void
    {
        config(['sentry.dsn' => 'https://test@sentry.io/123']);

        $hub = $this->createMock(HubInterface::class);
        $hub->method('getLastEventId')->willReturn(null);
        SentrySdk::setCurrentHub($hub);

        $html = view('errors.500')->render();

        $this->assertStringNotContainsString('browser.sentry-cdn.com', $html);
        $this->assertStringNotContainsString('Send error report', $html);
    }

    public function test_authenticated_user_data_pre_filled_in_script(): void
    {
        config(['sentry.dsn' => 'https://test@sentry.io/123']);

        $hub = $this->createMock(HubInterface::class);
        $hub->method('getLastEventId')->willReturn(
            new EventId('aaaabbbbccccddddaaaabbbbccccdddd')
        );
        SentrySdk::setCurrentHub($hub);

        $member = Member::factory()->create([
            'name' => 'Test Brewer',
            'email' => 'brewer@example.com',
        ]);

        $this->actingAs($member);

        $html = view('errors.500')->render();

        $this->assertStringContainsString('Test Brewer', $html);
        $this->assertStringContainsString('brewer@example.com', $html);
    }

    public function test_user_data_absent_when_guest(): void
    {
        config(['sentry.dsn' => 'https://test@sentry.io/123']);

        $hub = $this->createMock(HubInterface::class);
        $hub->method('getLastEventId')->willReturn(
            new EventId('aaaabbbbccccddddaaaabbbbccccdddd')
        );
        SentrySdk::setCurrentHub($hub);

        $html = view('errors.500')->render();

        $this->assertStringNotContainsString('"user"', $html);
        $this->assertStringNotContainsString('"name"', $html);
        $this->assertStringNotContainsString('"email"', $html);
    }
}
