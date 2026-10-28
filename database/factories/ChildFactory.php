<?php

namespace Database\Factories;

use App\Models\Child;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class ChildFactory extends Factory
{
    protected $model = Child::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => $this->faker->firstName(),
        ];
    }
}
