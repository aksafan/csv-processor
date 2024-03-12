<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Csv\Csv;
use App\Entity\CsvScheme\AbstractCsvSchemeEntity;
use Iterator;

interface CsvProcessorInterface
{
    public function processFacade(Csv $csv): bool;

    public function getRecords(Csv $csv): Iterator;

    public function processRecords(Iterator $records): bool;

    public function processRecord(AbstractCsvSchemeEntity $record): bool;
}