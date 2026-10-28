<?php

namespace Database\Seeders;

use App\Models\Tenant;
use Illuminate\Database\Seeder;

class TenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenants = [
            ['id' => 1, 'name' => 'Sunshine Nursery'],
            ['id' => 2, 'name' => 'Little Stars Preschool'],
        ];

        foreach ($tenants as $tenantData) {
            Tenant::updateOrCreate(
                ['id' => $tenantData['id']],
                ['name' => $tenantData['name']]
            );
        }
    }
}
