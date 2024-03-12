<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Csv\Csv;
use App\Entity\CsvScheme\AbstractCsvSchemeEntity;
use App\Entity\CsvScheme\Product;
use App\Entity\Exception\Domain\Reader\CsvReaderException;
use App\Entity\Exception\Domain\Reader\CsvReaderInvalidHeadersException;
use App\Entity\Exception\Domain\Reader\CsvRecordsUnSuccessfulProcessingException;
use App\Entity\Exception\Domain\Reader\CsvRecordUnSuccessfulProcessingException;
use App\Service\Factory\CsvReaderFactory;
use Iterator;
use League\Csv\Exception;
use League\Csv\Reader;
use League\Csv\SyntaxError;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class CsvProcessor implements CsvProcessorInterface
{
    public function __construct(
        private ValidatorInterface $validator,
        private CsvReaderFactory $csvReaderFactory,
    ) {
    }

    public function processFacade(Csv $csv): bool
    {
        $recordsToProcess = $this->getRecords($csv);

        return $this->processRecords($recordsToProcess);
    }

    public function getRecords(Csv $csv): Iterator
    {
        $csvReader = $this->csvReaderFactory->create($csv);

        $headers = $this->getHeaders($csvReader);

        try {
            return $csvReader->getRecordsAsObject(Product::class, $headers);
        } catch (Exception $exception) {
            throw new CsvReaderException($exception->getMessage());
        }
    }

    public function processRecords(Iterator $records): bool
    {
        $result = false;
        $errors = [];

        /** @var AbstractCsvSchemeEntity $record */
        foreach ($records as $record) {
            try {
                $result = $this->processRecord($record);
            } catch (CsvRecordUnSuccessfulProcessingException $exception) {
                $errors[$records->key() + 1] = $exception->errors;
            }
        }

        if ($errors) {
            throw new CsvRecordsUnSuccessfulProcessingException($errors);
        }

        return $result;
    }

    public function processRecord(AbstractCsvSchemeEntity $record): bool
    {
        $entityErrorList = $this->validator->validate($record);
        if (count($entityErrorList) > 0) {
            throw new CsvRecordUnSuccessfulProcessingException($entityErrorList);
        }

        // Do something else with a record
        // TODO: for future usage

        return true;
    }

    private function getHeaders(Reader $csvReader): array
    {
        try {
            $headers = $csvReader->getHeader();
            if (Product::getScheme() !== $headers) {
                throw new CsvReaderInvalidHeadersException(
                    sprintf('Headers must be equal to scheme: "%s"', implode(',', Product::getScheme()))
                );
            }
        } catch (SyntaxError $exception) {
            throw new CsvReaderInvalidHeadersException($exception->getMessage());
        }

        return $headers;
    }
}
