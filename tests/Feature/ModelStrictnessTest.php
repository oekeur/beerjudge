<?php

namespace Tests\Feature;

use App\Models\Scoresheet;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Tests\TestCase;

class ModelStrictnessTest extends TestCase
{
    public function test_mass_assigning_an_unfillable_attribute_throws_outside_production(): void
    {
        $this->expectException(MassAssignmentException::class);

        (new Scoresheet)->fill(['not_a_column' => 'x']);
    }
}
