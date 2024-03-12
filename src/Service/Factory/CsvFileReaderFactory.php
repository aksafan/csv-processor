<?php

declare(strict_types=1);

namespace App\Service\Factory;

use App\Entity\Csv\Csv;
use App\Entity\Exception\Domain\Reader\CsvReaderException;
use App\Entity\Exception\Domain\Reader\CsvReaderInvalidArgumentException;
use League\Csv\Exception;
use League\Csv\InvalidArgument;
use League\Csv\Reader;
use League\Csv\UnavailableStream;

class CsvFileReaderFactory
{
    public function create(Csv $csv): Reader
    {
        try {
            $csvReader = $this->getReader($csv);
            $csvReader->setHeaderOffset(0);
        } catch (InvalidArgument $exception) {
            throw new CsvReaderInvalidArgumentException($exception->getMessage());
        } catch (Exception $exception) {
            throw new CsvReaderException($exception->getMessage());
        }

        return $csvReader;
    }

    /**
     * @param Csv $csv
     *
     * @return Reader
     *
     * @throws Exception
     * @throws InvalidArgument
     * @throws UnavailableStream
     */
    protected function getReader(Csv $csv): Reader
    {
        return Reader::createFromPath($csv->csvFile->getPathname())
            ->setDelimiter($csv->delimiter)
            ->setEnclosure($csv->enclosure)
            ->setEscape($csv->escape);
    }
}
