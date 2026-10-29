<?php

namespace Database\Seeders;

use App\Enums\StaffRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Только для локальной разработки. Вход в админку: /admin
        User::updateOrCreate(
            ['email' => 'admin@avto.local'],
            ['name' => 'Администратор', 'role' => StaffRole::Admin, 'password' => 'password'],
        );

        $this->call(DemoCatalogSeeder::class);
    }
}
