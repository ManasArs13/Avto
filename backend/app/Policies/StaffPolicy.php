<?php

namespace App\Policies;

use App\Models\User;

/**
 * Общие права на записи в админке: смотреть могут все сотрудники,
 * менять — все, кроме роли «Только просмотр».
 * Filament проверяет эти методы для стандартных действий (создать, изменить, удалить).
 */
class StaffPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->role->canEdit();
    }

    public function update(User $user): bool
    {
        return $user->role->canEdit();
    }

    public function delete(User $user): bool
    {
        return $user->role->canEdit();
    }

    public function deleteAny(User $user): bool
    {
        return $user->role->canEdit();
    }

    public function replicate(User $user): bool
    {
        return $user->role->canEdit();
    }

    public function reorder(User $user): bool
    {
        return $user->role->canEdit();
    }
}
