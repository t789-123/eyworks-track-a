<?php

namespace Database\Factories;

use App\Models\CheckIn;
use App\Models\Child;
use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

class CheckInFactory extends Factory
{
    protected $model = CheckIn::class;

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'room_id' => Room::factory(),
            'child_id' => Child::factory(),
            'checked_in_at' => $this->faker->dateTimeThisMonth(),
            'checked_out_at' => null,
        ];
    }
}
