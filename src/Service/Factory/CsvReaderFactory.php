<?php

declare(strict_types=1);

namespace App\Service\Factory;

use App\Entity\Csv\Csv;
use App\Entity\Exception\Domain\Reader\CsvReaderException;
use App\Entity\Exception\Domain\Reader\CsvReaderInvalidArgumentException;
use League\Csv\Exception;
use League\Csv\InvalidArgument;
use League\Csv\Reader;

class CsvReaderFactory
{
    public function create(Csv $csv): Reader
    {
        try {
            return Reader::createFromPath($csv->csvFile->getPathname())
                ->setHeaderOffset(0)
                ->setDelimiter($csv->delimiter)
                ->setEnclosure($csv->enclosure)
                ->setEscape($csv->escape);
        } catch (InvalidArgument $exception) {
            throw new CsvReaderInvalidArgumentException($exception->getMessage());
        } catch (Exception $exception) {
            throw new CsvReaderException($exception->getMessage());
        }
    }
}