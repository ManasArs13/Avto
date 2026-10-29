<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasDescription;
use Filament\Support\Contracts\HasLabel;

/** Роль сотрудника в админке. */
enum StaffRole: string implements HasColor, HasDescription, HasLabel
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Viewer = 'viewer';

    public function getLabel(): string
    {
        return match ($this) {
            self::Admin => 'Администратор',
            self::Manager => 'Менеджер',
            self::Viewer => 'Только просмотр',
        };
    }

    public function getDescription(): string
    {
        return match ($this) {
            self::Admin => 'Всё, включая управление сотрудниками.',
            self::Manager => 'Заказы, каталог, прайсы и наценки. Без управления сотрудниками.',
            self::Viewer => 'Видит всё, ничего не может изменить.',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Admin => 'danger',
            self::Manager => 'primary',
            self::Viewer => 'gray',
        };
    }

    public function canEdit(): bool
    {
        return $this !== self::Viewer;
    }

    public function canManageStaff(): bool
    {
        return $this === self::Admin;
    }
}
