<?php

declare(strict_types=1);

namespace App\Enums;

enum UserRole: string
{
    case Admin = 'admin';
    case Manager = 'manager';
    case Staff = 'staff';

    public static function fromNullable(?string $role): self
    {
        return self::tryFrom((string) $role) ?? self::Admin;
    }

    public function label(): string
    {
        return __('roles.'.$this->value);
    }
}
