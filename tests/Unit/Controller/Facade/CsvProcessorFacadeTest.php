<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Facade;

use App\Controller\Service\CsvHandler;
use App\Entity\Csv\Csv;
use App\Entity\CsvScheme\Product;
use App\Entity\Exception\Domain\Reader\CsvReaderException;
use App\Service\CsvProcessor;
use App\Service\CsvRecordParserInterface;
use App\Service\CsvRecordsReaderInterface;
use ArrayIterator;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\File;

class CsvProcessorFacadeTest extends TestCase
{
    private const VALID_CSV_FILE = __DIR__ . '/../../Fixtures/valid.csv';
    private const INVALID_CSV_FILE = __DIR__ . '/../../Fixtures/invalid.csv';

    private CsvHandler $csvProcessorFacade;

    private CsvRecordsReaderInterface $csvRecordsReader;

    /**
     * @throws Exception
     */
    public function testProcessFacadeWithValidCsv(): void
    {
        $csv = $this->createCsv(self::VALID_CSV_FILE);
        $product = $this->createValidProduct();
        $iterator = new ArrayIterator([$product]);

        $this->csvRecordsReader
            ->method('read')
            ->willReturn($iterator);

        $result = $this->csvProcessorFacade->handle($csv);

        $this->assertFalse($result);
    }

    public function testProcessFacadeWithInvalidCsv(): void
    {
        $csv = $this->createCsv(self::INVALID_CSV_FILE);

        $this->csvRecordsReader
            ->method('read')
            ->willThrowException(new CsvReaderException());

        $this->expectException(CsvReaderException::class);

        $this->csvProcessorFacade->handle($csv);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->csvRecordsReader = $this->createMock(CsvRecordsReaderInterface::class);
        $csvRecordParser = $this->createMock(CsvRecordParserInterface::class);

        $this->csvProcessorFacade = new CsvHandler(new CsvProcessor($csvRecordParser), $this->csvRecordsReader);
    }

    private function createCsv(string $pathToCsv): Csv
    {
        return new Csv(new File($pathToCsv));
    }

    private function createValidProduct(): Product
    {
        return new Product(
            'Product',
            '"Product Sample 1"',
            'Physical',
            'TY-1',
            53,
            354,
            3,
            '"Lorem Ipsum is simply dummy text of the printing and typesetting industry. ID = 1. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum."',
            0.1,
            4.6,
            2.2,
            false
        );
    }
}
