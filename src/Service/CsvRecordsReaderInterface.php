<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Csv\Csv;
use Iterator;

interface CsvRecordsReaderInterface
{
    public function read(Csv $csv): Iterator;
}
