<?php

namespace App\Enums;

enum Role: string
{
    case CLIENT = 'CLIENT';

    case SHOPOWNER = 'SHOPOWNER';
  
    case ADMIN = 'ADMIN';
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
