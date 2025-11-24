<?php

namespace App\Enums;

enum AuthProviderEnum: string
{
    case LOCAL = 'local'; // email and password
    case GOOGLE = 'google';
    case FACEBOOK = 'facebook';
    case APPLE = 'apple';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
