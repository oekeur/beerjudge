<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Queue\Failed\FailedJobProviderInterface;
use RuntimeException;
use Tests\TestCase;

class FailedJobsTableTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_a_job_that_runs_out_of_tries_is_recorded_for_retry(): void
    {
        $failer = $this->app->make(FailedJobProviderInterface::class);

        $failer->log('redis', 'default', json_encode(['uuid' => 'c6d1c2a4-0000-4000-8000-000000000001', 'displayName' => 'SendResults']), new RuntimeException('SMTP down'));

        $this->assertCount(1, $failer->all());
        $this->assertStringContainsString('SMTP down', $failer->all()[0]->exception);
    }
}
