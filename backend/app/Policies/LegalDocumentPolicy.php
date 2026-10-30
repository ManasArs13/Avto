<?php

namespace App\Policies;

use App\Models\User;

/**
 * Документы читают все сотрудники, меняет только администратор.
 * Набор документов фиксирован (на них ссылается журнал согласий), поэтому создавать и удалять нельзя.
 */
class LegalDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user): bool
    {
        return true;
    }

    public function update(User $user): bool
    {
        return $user->role->canManageStaff();
    }

    public function create(User $user): bool
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
