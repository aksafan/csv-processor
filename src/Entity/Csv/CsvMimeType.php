<?php

declare(strict_types=1);

namespace App\Entity\Csv;

interface CsvMimeType
{
    /**
     * @var string[] Grabbed from https://stackoverflow.com/a/42140178
     */
    public const DEFAULT_TYPES = [
        'application/vnd.ms-excel',
        'application/excel',
        'application/csv',
        'application/x-csv',
        'text/x-comma-separated-values',
        'text/comma-separated-values',
        'text/plain',
        'text/csv',
        'text/x-csv',
    ];
}
