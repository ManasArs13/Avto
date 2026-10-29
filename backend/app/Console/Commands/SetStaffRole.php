<?php

namespace App\Console\Commands;

use App\Enums\StaffRole;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

/**
 * Пользователь из make:filament-user получает роль «Только просмотр».
 * Первого администратора на сервере назначаем этой командой, дальше — в админке.
 */
#[Signature('staff:role {email : Email сотрудника} {role : admin, manager или viewer}')]
#[Description('Назначить роль сотруднику админки')]
class SetStaffRole extends Command
{
    public function handle(): int
    {
        $role = StaffRole::tryFrom($this->argument('role'));

        if (! $role) {
            $this->error('Неизвестная роль. Допустимо: '.implode(', ', array_column(StaffRole::cases(), 'value')));

            return self::FAILURE;
        }

        $user = User::where('email', $this->argument('email'))->first();

        if (! $user) {
            $this->error('Сотрудник не найден: '.$this->argument('email'));

            return self::FAILURE;
        }

        $user->update(['role' => $role]);
        $this->info("{$user->name}: {$role->getLabel()}");

        return self::SUCCESS;
    }
}
