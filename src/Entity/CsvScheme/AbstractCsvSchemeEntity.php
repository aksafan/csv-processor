<?php

declare(strict_types=1);

namespace App\Entity\CsvScheme;

readonly abstract class AbstractCsvSchemeEntity
{
    public static function getScheme(): array
    {
        return array_keys(get_class_vars(static::class));
    }
}
