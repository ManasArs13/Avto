<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Только для локальной разработки. Вход в админку: /admin
        User::updateOrCreate(
            ['email' => 'admin@avto.local'],
            ['name' => 'Администратор', 'password' => 'password'],
        );

        $this->call(DemoCatalogSeeder::class);
    }
}
