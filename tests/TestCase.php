<?php

namespace Tests;

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    public function createApplication(): Application
    {
        $_ENV['APP_BASE_PATH'] = dirname(__DIR__);

        return parent::createApplication();
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }
}
