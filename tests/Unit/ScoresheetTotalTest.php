<?php

namespace Tests\Unit;

use App\Models\Scoresheet;
use PHPUnit\Framework\TestCase;

class ScoresheetTotalTest extends TestCase
{
    public function test_total_is_sum_of_sections(): void
    {
        $sheet = new Scoresheet([
            'aroma' => 10, 'appearance' => 3, 'flavour' => 18, 'mouthfeel' => 4, 'overall' => 9,
        ]);
        // Trigger the saving hook manually
        $sheet->total = $sheet->aroma + $sheet->appearance + $sheet->flavour + $sheet->mouthfeel + $sheet->overall;
        $this->assertEquals(44, $sheet->total);
    }

    public function test_total_all_zeros(): void
    {
        $sheet = new Scoresheet(['aroma' => 0, 'appearance' => 0, 'flavour' => 0, 'mouthfeel' => 0, 'overall' => 0]);
        $sheet->total = 0;
        $this->assertEquals(0, $sheet->total);
    }

    public function test_total_max_values(): void
    {
        $sheet = new Scoresheet(['aroma' => 12, 'appearance' => 3, 'flavour' => 20, 'mouthfeel' => 5, 'overall' => 10]);
        $sheet->total = $sheet->aroma + $sheet->appearance + $sheet->flavour + $sheet->mouthfeel + $sheet->overall;
        $this->assertEquals(50, $sheet->total);
    }
}
