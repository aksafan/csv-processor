<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Csv\Csv;
use App\Entity\CsvScheme\Product;
use App\Entity\Exception\Domain\Reader\CsvReaderException;
use App\Entity\Exception\Domain\Reader\CsvReaderInvalidHeadersException;
use App\Service\Factory\CsvFileReaderFactory;
use Iterator;
use League\Csv\Exception;
use League\Csv\Reader;
use League\Csv\SyntaxError;

final readonly class CsvRecordsReader implements CsvRecordsReaderInterface
{
    public function __construct(
        private CsvFileReaderFactory $csvReaderFactory
    ) {
    }

    public function read(Csv $csv): Iterator
    {
        $csvReader = $this->csvReaderFactory->create($csv);

        $headers = $this->getHeaders($csvReader);

        try {
            return $csvReader->getRecordsAsObject(Product::class, $headers);
        } catch (Exception $exception) {
            throw new CsvReaderException($exception->getMessage());
        }
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
