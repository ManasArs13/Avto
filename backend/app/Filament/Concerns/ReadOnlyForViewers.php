<?php

namespace App\Filament\Concerns;

use Filament\Facades\Filament;

/**
 * Для relation manager'ов: роль «Только просмотр» не может ничего добавить,
 * изменить, привязать или отвязать. Attach/Detach Filament проверяет только через isReadOnly().
 */
trait ReadOnlyForViewers
{
    public function isReadOnly(): bool
    {
        return parent::isReadOnly() || ! Filament::auth()->user()?->role->canEdit();
    }
}
