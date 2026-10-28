<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            TenantSeeder::class,
            RoomSeeder::class,
            ChildSeeder::class,
            CheckInSeeder::class,
        ]);
    }
}
