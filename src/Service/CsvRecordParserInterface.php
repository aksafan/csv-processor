<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\CsvScheme\AbstractCsvSchemeEntity;

interface CsvRecordParserInterface
{
    public function parse(AbstractCsvSchemeEntity $record): bool;
}
