<?php

namespace App\Enums;

enum StatutCommande: string
{
    case enCours = 'enCours';
    case payee = 'payee';
    case recuperer = 'recuperer';
}