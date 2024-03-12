<?php

declare(strict_types=1);

namespace App\Entity\Csv;

interface CsvProperties
{
    public const DELIMITER = ',';
    public const ENCLOSURE = '"';
    public const ESCAPE = '\\';
}
