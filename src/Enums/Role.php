<?php

namespace App\Enums;

enum Role: string
{
    case ADMIN = 'ADMIN';
    case SHOPOWNER = 'SHOPOWNER';
    case CLIENT = 'CLIENT';
}
