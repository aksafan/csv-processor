<?php

namespace App\Tests\Unit\Service\Factory;

use App\Entity\Csv\CsvGenerator;
use App\Entity\Csv\CsvProperties;
use App\Entity\Exception\Domain\Writer\CsvWriterException;
use App\Entity\Exception\Domain\Writer\CsvWriterInvalidArgumentException;
use App\Service\Factory\CsvFileWriterFactory;
use League\Csv\Exception;
use League\Csv\Writer;
use PHPUnit\Framework\TestCase;

class CsvWriterFactoryTest extends TestCase
{
    private const VALID_FILE_PATH = __DIR__ . '/../Fixtures/valid.csv';
    private const NUMBER_OF_RECORDS = 100;
    private const INVALID_DELIMITER = 'efwef';

    private CsvFileWriterFactory $csvWriterFactory;
    private mixed $outputStream;

    protected function setUp(): void
    {
        $this->csvWriterFactory = new CsvFileWriterFactory();
        $this->outputStream = fopen('php://memory', 'w+');
    }

    protected function tearDown(): void
    {
        fclose($this->outputStream);
    }

    public function testCreateWithValidArguments(): void
    {
        $csvGenerator = $this->getValidCsvGenerator();

        $csvWriter = $this->csvWriterFactory->create($csvGenerator, $this->outputStream);

        $this->assertInstanceOf(Writer::class, $csvWriter);
    }

    public function testCreateWithInvalidArguments(): void
    {
        $csvGenerator = $this->getInvalidCsvGenerator();

        $this->expectException(CsvWriterInvalidArgumentException::class);

        $this->csvWriterFactory->create($csvGenerator, $this->outputStream);
    }

    public function testCreateWithExceptionThrown(): void
    {
        $csvGenerator = $this->createMock(CsvGenerator::class);

        $writer = $this->createMock(Writer::class);
        $writer->method('insertOne')
            ->willThrowException(new Exception());

        $factory = $this->getMockBuilder(CsvFileWriterFactory::class)
            ->onlyMethods(['getWriter'])
            ->getMock();
        $factory
            ->expects($this->once())
            ->method('getWriter')
            ->willReturn($writer);

        $this->expectException(CsvWriterException::class);

        $factory->create($csvGenerator, $this->outputStream);
    }

    private function getValidCsvGenerator(): CsvGenerator
    {
        return new CsvGenerator(
            self::VALID_FILE_PATH,
            self::NUMBER_OF_RECORDS,
            CsvProperties::DELIMITER,
            CsvProperties::ENCLOSURE,
            CsvProperties::ESCAPE
        );
    }

    private function getInvalidCsvGenerator(): CsvGenerator
    {
        return new CsvGenerator(
            self::VALID_FILE_PATH,
            self::NUMBER_OF_RECORDS,
            self::INVALID_DELIMITER,
            CsvProperties::ENCLOSURE,
            CsvProperties::ESCAPE
        );
    }
}
