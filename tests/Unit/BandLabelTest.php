<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class BandLabelTest extends TestCase
{
    private function band(float $total): string
    {
        return match (true) {
            $total >= 45 => 'Outstanding',
            $total >= 38 => 'Excellent',
            $total >= 30 => 'Very Good',
            $total >= 21 => 'Good',
            $total >= 14 => 'Fair',
            default => 'Problematic',
        };
    }

    public function test_outstanding(): void
    {
        $this->assertEquals('Outstanding', $this->band(45));
    }

    public function test_excellent_upper(): void
    {
        $this->assertEquals('Excellent', $this->band(44));
    }

    public function test_excellent_lower(): void
    {
        $this->assertEquals('Excellent', $this->band(38));
    }

    public function test_very_good_upper(): void
    {
        $this->assertEquals('Very Good', $this->band(37));
    }

    public function test_very_good_lower(): void
    {
        $this->assertEquals('Very Good', $this->band(30));
    }

    public function test_good_upper(): void
    {
        $this->assertEquals('Good', $this->band(29));
    }

    public function test_good_lower(): void
    {
        $this->assertEquals('Good', $this->band(21));
    }

    public function test_fair_upper(): void
    {
        $this->assertEquals('Fair', $this->band(20));
    }

    public function test_fair_lower(): void
    {
        $this->assertEquals('Fair', $this->band(14));
    }

    public function test_problematic(): void
    {
        $this->assertEquals('Problematic', $this->band(13));
    }

    public function test_zero(): void
    {
        $this->assertEquals('Problematic', $this->band(0));
    }
}
