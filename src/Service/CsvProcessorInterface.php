<?php

declare(strict_types=1);

namespace App\Service;

use Iterator;

interface CsvProcessorInterface
{
    public function processRecords(Iterator $records): bool;
}