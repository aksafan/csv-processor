<?php

declare(strict_types=1);

namespace App\Service\Factory;

use App\Entity\Csv\CsvGenerator;
use App\Entity\CsvScheme\Product;
use App\Entity\Exception\Domain\Writer\CsvWriterException;
use App\Entity\Exception\Domain\Writer\CsvWriterInvalidArgumentException;
use League\Csv\Exception;
use League\Csv\InvalidArgument;
use League\Csv\Writer;

class CsvWriterFactory
{
    public function create(CsvGenerator $csvGenerator, mixed $outputStream): Writer
    {
        try {
            $csvWriter = $this->getWriter($outputStream, $csvGenerator);
            $csvWriter->insertOne(Product::getScheme());
        } catch (InvalidArgument $exception) {
            throw new CsvWriterInvalidArgumentException($exception->getMessage());
        } catch (Exception $exception) {
            throw new CsvWriterException($exception->getMessage());
        }

        return $csvWriter;
    }

    /**
     * @param mixed $outputStream
     * @param CsvGenerator $csvGenerator
     *
     * @return Writer
     *
     * @throws InvalidArgument
     */
    protected function getWriter(mixed $outputStream, CsvGenerator $csvGenerator): Writer
    {
        return Writer::createFromStream($outputStream)
            ->setDelimiter($csvGenerator->delimiter)
            ->setEnclosure($csvGenerator->enclosure)
            ->setEscape($csvGenerator->escape);
    }
}