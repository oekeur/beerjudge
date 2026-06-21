<?php

namespace Tests\Unit;

use App\Services\CompetitionRankingService;
use PHPUnit\Framework\TestCase;

class RankingTest extends TestCase
{
    /** @param array<int, array{avg_total: float, avg_overall: float}> $rows */
    private function rank(array $rows): array
    {
        return CompetitionRankingService::sortAndPlace(collect($rows))->all();
    }

    public function test_ranks_by_avg_total_descending(): void
    {
        $rows = [
            ['name' => 'Low', 'avg_total' => 28.0, 'avg_overall' => 6.0],
            ['name' => 'High', 'avg_total' => 42.0, 'avg_overall' => 8.0],
        ];
        $result = $this->rank($rows);
        $this->assertEquals('High', $result[0]['name']);
        $this->assertEquals(1, $result[0]['placement']);
    }

    public function test_tiebreak_uses_avg_overall(): void
    {
        $rows = [
            ['name' => 'LowOverall', 'avg_total' => 35.0, 'avg_overall' => 6.0],
            ['name' => 'HighOverall', 'avg_total' => 35.0, 'avg_overall' => 9.0],
        ];
        $result = $this->rank($rows);
        $this->assertEquals('HighOverall', $result[0]['name']);
    }

    public function test_single_entry_gets_placement_1(): void
    {
        $rows = [['name' => 'Solo', 'avg_total' => 30.0, 'avg_overall' => 7.0]];
        $result = $this->rank($rows);
        $this->assertEquals(1, $result[0]['placement']);
    }
}
