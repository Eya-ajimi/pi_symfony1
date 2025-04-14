<?php

namespace App\Enum;

enum RoleEnum: string
{
    case CLIENT = 'CLIENT';

    case SHOPOWNER = 'SHOPOWNER';
  
    case ADMIN = 'ADMIN';
}
