<?php

declare(strict_types=1);

namespace App\Controller\Service;

use App\Entity\Csv\Csv;
use App\Service\CsvProcessorInterface;
use App\Service\CsvRecordsReaderInterface;

readonly class CsvHandler
{
    public function __construct(
        private CsvProcessorInterface $csvProcessor,
        private CsvRecordsReaderInterface $csvRecordsReader
    ) {
    }

    public function handle(Csv $csv): bool
    {
        $recordsToProcess = $this->csvRecordsReader->read($csv);

        return $this->csvProcessor->processRecords($recordsToProcess);
    }
}
