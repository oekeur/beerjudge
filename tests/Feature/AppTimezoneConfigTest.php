<?php

namespace Tests\Feature;

use Illuminate\Support\Env;
use Tests\TestCase;

class AppTimezoneConfigTest extends TestCase
{
    protected function tearDown(): void
    {
        $this->setTimezoneEnv(null);

        parent::tearDown();
    }

    public function test_timezone_defaults_to_utc(): void
    {
        $this->setTimezoneEnv(null);

        $this->assertSame('UTC', $this->loadAppConfig()['timezone']);
    }

    public function test_timezone_reads_app_timezone(): void
    {
        $this->setTimezoneEnv('Europe/Amsterdam');

        $this->assertSame('Europe/Amsterdam', $this->loadAppConfig()['timezone']);
    }

    /**
     * @return array<string, mixed>
     */
    private function loadAppConfig(): array
    {
        return require config_path('app.php');
    }

    private function setTimezoneEnv(?string $value): void
    {
        if ($value === null) {
            putenv('APP_TIMEZONE');
            unset($_ENV['APP_TIMEZONE'], $_SERVER['APP_TIMEZONE']);
        } else {
            putenv("APP_TIMEZONE={$value}");
            $_ENV['APP_TIMEZONE'] = $value;
            $_SERVER['APP_TIMEZONE'] = $value;
        }

        Env::enablePutenv();
    }
}
