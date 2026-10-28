<?php

namespace Database\Seeders;

use App\Models\Child;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class ChildSeeder extends Seeder
{
    public function run(): void
    {
        $children = [
            ['id' => 1, 'tenant_id' => 1, 'name' => 'Aarav Sharma'],
            ['id' => 2, 'tenant_id' => 1, 'name' => 'Ananya Patel'],
            ['id' => 3, 'tenant_id' => 2, 'name' => 'Vivaan Verma'],
            ['id' => 4, 'tenant_id' => 2, 'name' => 'Priya Singh'],
        ];

        foreach ($children as $childData) {
            $tenant = Tenant::query()->findOrFail($childData['tenant_id']);

            Child::updateOrCreate(
                ['id' => $childData['id']],
                [
                    'tenant_id' => $tenant->id,
                    'name' => $childData['name'],
                ]
            );
        }
    }
}
