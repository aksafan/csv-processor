<?php

declare(strict_types=1);

namespace App\Entity\Csv;

readonly abstract class AbstractCsvEntity
{
    public static function getScheme(): array
    {
        return array_keys(get_class_vars(static::class));
    }
}
