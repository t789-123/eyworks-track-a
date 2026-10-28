<?php

namespace Database\Seeders;

use App\Models\CheckIn;
use App\Models\Child;
use App\Models\Room;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class CheckInSeeder extends Seeder
{
    public function run(): void
    {
        $checkIns = [
            [
                'id' => 1,
                'tenant_id' => 1,
                'room_id' => 1,
                'child_id' => 1,
                'checked_in_at' => '2026-10-28 08:30:00',
                'checked_out_at' => null,
            ],
            [
                'id' => 2,
                'tenant_id' => 1,
                'room_id' => 2,
                'child_id' => 2,
                'checked_in_at' => '2026-10-28 08:00:00',
                'checked_out_at' => '2026-10-28 09:00:00',
            ],
            [
                'id' => 3,
                'tenant_id' => 2,
                'room_id' => 3,
                'child_id' => 3,
                'checked_in_at' => '2026-10-28 08:45:00',
                'checked_out_at' => null,
            ],
            [
                'id' => 4,
                'tenant_id' => 2,
                'room_id' => 4,
                'child_id' => 4,
                'checked_in_at' => '2026-10-28 07:55:00',
                'checked_out_at' => '2026-10-28 09:15:00',
            ],
        ];

        foreach ($checkIns as $checkInData) {
            $tenant = Tenant::query()->findOrFail($checkInData['tenant_id']);
            $room = $tenant->rooms()->findOrFail($checkInData['room_id']);
            $child = $tenant->children()->findOrFail($checkInData['child_id']);

            CheckIn::updateOrCreate(
                ['id' => $checkInData['id']],
                [
                    'tenant_id' => $tenant->id,
                    'room_id' => $room->id,
                    'child_id' => $child->id,
                    'checked_in_at' => $checkInData['checked_in_at'],
                    'checked_out_at' => $checkInData['checked_out_at'],
                ]
            );
        }
    }
}
