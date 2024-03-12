<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\CsvScheme\AbstractCsvSchemeEntity;
use App\Entity\Exception\Domain\Reader\CsvRecordUnSuccessfulProcessingException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class CsvRecordParser implements CsvRecordParserInterface
{
    public function __construct(
        private ValidatorInterface $validator
    ) {
    }

    public function parse(AbstractCsvSchemeEntity $record): bool
    {
        $entityErrorList = $this->validator->validate($record);
        if (count($entityErrorList) > 0) {
            throw new CsvRecordUnSuccessfulProcessingException($entityErrorList);
        }

        // Do something else with a record
        // TODO: for future usage

        return true;
    }
}
