<?php

namespace App\Policies;

use App\Models\User;

/** Журнал согласий: только чтение и только администратору (в нём IP-адреса покупателей). */
class ConsentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role->canManageStaff();
    }

    public function view(User $user): bool
    {
        return $user->role->canManageStaff();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user): bool
    {
        return false;
    }

    public function delete(User $user): bool
    {
        return false;
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
