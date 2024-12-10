<?php

namespace App\Enum;

enum UserRoleEnum: string
{
    case ROLE_USER = 'ROLE_USER';
    case ROLE_COMPANY_ADMIN = 'ROLE_COMPANY_ADMIN';
    case ROLE_SUPER_ADMIN = 'ROLE_SUPER_ADMIN';

    public static function isValid(?string $value): bool
    {
        return in_array($value, array_column(self::cases(), 'value'), true);
    }
}
