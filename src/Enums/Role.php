<?php

namespace App\Enum;

enum Role: string
{
    case SHOPOWNER = 'SHOPOWNER';
    case ADMIN = 'ADMIN';
    case CLIENT = 'CLIENT';
    
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}