<?php

namespace Database\Factories;

use App\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    protected $model = Member::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'password' => Hash::make('password'),
            'is_admin' => false,
            'is_active' => true,
        ];
    }

    public function admin(): static
    {
        return $this->state(['is_admin' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
