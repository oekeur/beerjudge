<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class RankingTest extends TestCase
{
    private function rank(array $rows): array
    {
        $sorted = collect($rows)->sort(fn($a, $b) =>
            $b['avg_total'] <=> $a['avg_total'] ?: $b['avg_overall'] <=> $a['avg_overall']
        )->values()->toArray();

        foreach ($sorted as $i => &$row) {
            $row['placement'] = $i + 1;
        }
        return $sorted;
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
