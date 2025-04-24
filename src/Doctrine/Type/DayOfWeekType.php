<?php
// src/Doctrine/Type/DayOfWeekType.php
namespace App\Doctrine\Type;

use App\Enum\DayOfWeek;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\Type;

class DayOfWeekType extends Type
{
    public function getSQLDeclaration(array $column, AbstractPlatform $platform): string
    {
        return "ENUM('MONDAY','TUESDAY','WEDNESDAY','THURSDAY','FRIDAY','SATURDAY','SUNDAY')";
    }

    public function convertToPHPValue($value, AbstractPlatform $platform): ?DayOfWeek
    {
        return $value !== null ? DayOfWeek::from($value) : null;
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform): ?string
    {
        return $value instanceof DayOfWeek ? $value->value : null;
    }

    public function getName(): string
    {
        return 'DayOfWeek';
    }
}