<?php

namespace Tests\Unit;

use App\Services\CompetitionRankingService;
use PHPUnit\Framework\TestCase;

class BandLabelTest extends TestCase
{
    public function test_outstanding(): void
    {
        $this->assertEquals('Outstanding', (new CompetitionRankingService)->band(45));
    }

    public function test_excellent_upper(): void
    {
        $this->assertEquals('Excellent', (new CompetitionRankingService)->band(44));
    }

    public function test_excellent_lower(): void
    {
        $this->assertEquals('Excellent', (new CompetitionRankingService)->band(38));
    }

    public function test_very_good_upper(): void
    {
        $this->assertEquals('Very Good', (new CompetitionRankingService)->band(37));
    }

    public function test_very_good_lower(): void
    {
        $this->assertEquals('Very Good', (new CompetitionRankingService)->band(30));
    }

    public function test_good_upper(): void
    {
        $this->assertEquals('Good', (new CompetitionRankingService)->band(29));
    }

    public function test_good_lower(): void
    {
        $this->assertEquals('Good', (new CompetitionRankingService)->band(21));
    }

    public function test_fair_upper(): void
    {
        $this->assertEquals('Fair', (new CompetitionRankingService)->band(20));
    }

    public function test_fair_lower(): void
    {
        $this->assertEquals('Fair', (new CompetitionRankingService)->band(14));
    }

    public function test_problematic(): void
    {
        $this->assertEquals('Problematic', (new CompetitionRankingService)->band(13));
    }

    public function test_zero(): void
    {
        $this->assertEquals('Problematic', (new CompetitionRankingService)->band(0));
    }
}
