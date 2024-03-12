<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\CsvScheme\AbstractCsvSchemeEntity;
use App\Entity\Exception\Domain\Reader\CsvRecordsUnSuccessfulProcessingException;
use App\Entity\Exception\Domain\Reader\CsvRecordUnSuccessfulProcessingException;
use Iterator;

final readonly class CsvProcessor implements CsvProcessorInterface
{
    public function __construct(
        private CsvRecordParserInterface $csvRecordParser
    ) {
    }

    public function processRecords(Iterator $records): bool
    {
        $result = false;
        $errors = [];
        /** @var AbstractCsvSchemeEntity $record */
        foreach ($records as $record) {
            try {
                $result = $this->csvRecordParser->parse($record);
            } catch (CsvRecordUnSuccessfulProcessingException $exception) {
                $errors[$records->key() + 1] = $exception->errors;
            }
        }

        if ($errors) {
            throw new CsvRecordsUnSuccessfulProcessingException($errors);
        }

        return $result;
    }
}
