<?php

namespace Database\Seeders;

use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class RoomSeeder extends Seeder
{
    public function run(): void
    {
        $rooms = [
            ['id' => 1, 'tenant_id' => 1, 'name' => 'Sunflowers'],
            ['id' => 2, 'tenant_id' => 1, 'name' => 'Peacocks'],
            ['id' => 3, 'tenant_id' => 2, 'name' => 'Butterflies'],
            ['id' => 4, 'tenant_id' => 2, 'name' => 'Sunflowers'],
        ];

        foreach ($rooms as $roomData) {
            $tenant = Tenant::query()->findOrFail($roomData['tenant_id']);

            Room::updateOrCreate(
                ['id' => $roomData['id']],
                [
                    'tenant_id' => $tenant->id,
                    'name' => $roomData['name'],
                ]
            );
        }
    }
}
