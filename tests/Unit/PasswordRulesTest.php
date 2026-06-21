<?php

namespace Tests\Unit;

use App\Support\PasswordRules;
use PHPUnit\Framework\TestCase;

class PasswordRulesTest extends TestCase
{
    public function test_required_contains_required_rule(): void
    {
        $this->assertContains('required', PasswordRules::required());
    }

    public function test_optional_contains_nullable_rule(): void
    {
        $this->assertContains('nullable', PasswordRules::optional());
    }

    public function test_required_and_optional_share_common_rules(): void
    {
        $required = PasswordRules::required();
        $optional = PasswordRules::optional();

        $this->assertContains('min:8', $required);
        $this->assertContains('confirmed', $required);
        $this->assertContains('min:8', $optional);
        $this->assertContains('confirmed', $optional);
    }

    public function test_required_and_optional_differ_only_in_first_rule(): void
    {
        $required = PasswordRules::required();
        $optional = PasswordRules::optional();

        $this->assertNotEquals($required[0], $optional[0]);
        $this->assertEquals(array_slice($required, 1), array_slice($optional, 1));
    }
}
