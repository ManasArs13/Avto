<?php

namespace App\Policies;

use App\Models\User;

/** Сотрудниками управляет только администратор. */
class UserPolicy
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
        return $user->role->canManageStaff();
    }

    public function update(User $user): bool
    {
        return $user->role->canManageStaff();
    }

    public function delete(User $user, User $model): bool
    {
        // Себя удалить нельзя — иначе можно остаться без администратора.
        return $user->role->canManageStaff() && $user->isNot($model);
    }

    public function deleteAny(User $user): bool
    {
        return false;
    }
}
