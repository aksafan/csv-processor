<?php

namespace App\Tests\Unit\Service\Factory;

use App\Entity\Csv\Csv;
use App\Entity\Exception\Domain\Reader\CsvReaderException;
use App\Entity\Exception\Domain\Reader\CsvReaderInvalidArgumentException;
use App\Service\Factory\CsvReaderFactory;
use League\Csv\Exception;
use League\Csv\Reader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\File;

class CsvReaderFactoryTest extends TestCase
{
    private const VALID_FILE_PATH = __DIR__ . '/../../Fixtures/valid.csv';
    private const INVALID_DELIMITER = 'efwef';

    private CsvReaderFactory $csvReaderFactory;

    protected function setUp(): void
    {
        $this->csvReaderFactory = new CsvReaderFactory();
    }

    public function testCreateWithValidArguments(): void
    {
        $csv = $this->getValidCsv();

        $csvReader = $this->csvReaderFactory->create($csv);

        $this->assertInstanceOf(Reader::class, $csvReader);
    }

    public function testCreateWithInvalidArguments(): void
    {
        $csv = $this->getInvalidCsv();

        $this->expectException(CsvReaderInvalidArgumentException::class);

        $this->csvReaderFactory->create($csv);
    }

    public function testCreateWithExceptionThrown(): void
    {
        $csv = $this->createMock(Csv::class);

        $reader = $this->createMock(Reader::class);
        $reader->method('setHeaderOffset')
            ->willThrowException(new Exception());

        $factory = $this->getMockBuilder(CsvReaderFactory::class)
            ->onlyMethods(['getReader'])
            ->getMock();
        $factory
            ->expects($this->once())
            ->method('getReader')
            ->willReturn($reader);

        $this->expectException(CsvReaderException::class);

        $factory->create($csv);
    }

    private function getValidCsv(): Csv
    {
        return new Csv(
            new File(self::VALID_FILE_PATH)
        );
    }

    private function getInvalidCsv(): Csv
    {
        return new Csv(
            new File(self::VALID_FILE_PATH),
            self::INVALID_DELIMITER
        );
    }
}
