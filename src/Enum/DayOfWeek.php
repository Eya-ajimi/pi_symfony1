<?php
// src/Enum/DayOfWeek.php
namespace App\Enum;

enum DayOfWeek: string
{
    case MONDAY = 'MONDAY';
    case TUESDAY = 'TUESDAY';
    case WEDNESDAY = 'WEDNESDAY';
    case THURSDAY = 'THURSDAY';
    case FRIDAY = 'FRIDAY';
    case SATURDAY = 'SATURDAY';
    case SUNDAY = 'SUNDAY';

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}