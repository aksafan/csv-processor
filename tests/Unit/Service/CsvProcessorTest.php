<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Csv\Csv;
use App\Entity\CsvScheme\Product;
use App\Entity\Exception\Domain\Reader\CsvReaderException;
use App\Entity\Exception\Domain\Reader\CsvReaderInvalidHeadersException;
use App\Entity\Exception\Domain\Reader\CsvRecordsUnSuccessfulProcessingException;
use App\Service\CsvProcessor;
use App\Service\CsvProcessorInterface;
use App\Service\Factory\CsvReaderFactory;
use ArrayIterator;
use Iterator;
use League\Csv\Reader;
use League\Csv\SyntaxError;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\MockObject\MockBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CsvProcessorTest extends TestCase
{
    private const VALID_CSV_FILE = __DIR__ . '/../Fixtures/valid.csv';
    private const INVALID_CSV_FILE = __DIR__ . '/../Fixtures/invalid.csv';

    private const VALID_CSV_HEADERS = [
        'item',
        'name',
        'type',
        'sku',
        'stock',
        'price',
        'category',
        'description',
        'weight',
        'width',
        'height',
        'visible',
    ];

    private const INVALID_CSV_HEADERS = [
        'item1',
        'name',
        'type2',
        'sku',
        'stock',
        'price1',
        'category',
        'description',
        'weight',
        'width',
        'height',
        'visible',
    ];

    private const SYNTAX_ERROR_EXCEPTION_MESSAGE = 'The header record contains non string colum names.';

    private const CSV_READER_RUNTIME_EXCEPTION_MESSAGE = 'Csv Reader Runtime Exception';

    private const VIOLATION_ERROR_MESSAGE = 'The mime type of the file is invalid.';

    private CsvProcessorInterface $csvProcessor;

    private ValidatorInterface $validator;

    private CsvReaderFactory $csvReaderFactory;

    /**
     * @throws Exception
     */
    public function testProcessFacadeWithValidCsv(): void
    {
        $csv = $this->createCsv(self::VALID_CSV_FILE);
        $product = $this->createValidProduct();
        $iterator = new ArrayIterator([$product]);

        $this->csvReaderFactory
            ->expects($this->once())
            ->method('create')
            ->with($csv)
            ->willReturn($this->createMockReaderWithValidHeadersAndRecords($iterator));

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->willReturn($this->createMockViolationList());

        $result = $this->csvProcessor->processFacade($csv);

        $this->assertTrue($result);
    }

    public function testProcessFacadeWithInvalidCsv(): void
    {
        $csv = $this->createCsv(self::INVALID_CSV_FILE);
        $product = $this->createValidProduct();
        $iterator = new ArrayIterator([$product]);

        $this->csvReaderFactory
            ->expects($this->once())
            ->method('create')
            ->with($csv)
            ->willReturn($this->createMockReaderWithValidHeadersAndRecords($iterator));

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->willReturn($this->createMockViolationListWithErrors());

        $this->expectException(CsvRecordsUnSuccessfulProcessingException::class);

        $this->csvProcessor->processFacade($csv);
    }

    /**
     * @throws Exception
     */
    public function testGetRecordsWithValidCsv(): void
    {
        $csv = $this->createCsv(self::VALID_CSV_FILE);
        $product = $this->createValidProduct();
        $iterator = new ArrayIterator([$product]);

        $this->csvReaderFactory
            ->expects($this->once())
            ->method('create')
            ->with($csv)
            ->willReturn($this->createMockReaderWithValidHeadersAndRecords($iterator));

        $result = $this->csvProcessor->getRecords($csv);

        $this->assertIsIterable($result);
    }

    /**
     * @throws Exception
     */
    public function testGetRecordsWithInvalidHeader(): void
    {
        $csv = $this->createCsv(self::VALID_CSV_FILE);
        $product = $this->createValidProduct();
        $iterator = new ArrayIterator([$product]);

        $this->csvReaderFactory
            ->expects($this->once())
            ->method('create')
            ->with($csv)
            ->willReturn($this->createMockReaderWithInvalidHeadersAndNoRecords($iterator));

        $this->expectException(CsvReaderInvalidHeadersException::class);
        $this->expectExceptionMessage(
            sprintf('Headers must be equal to scheme: "%s"', implode(',', self::VALID_CSV_HEADERS))
        );

        $this->csvProcessor->getRecords($csv);
    }

    /**
     * @throws Exception
     */
    public function testGetRecordsWithSyntaxErrorHeader(): void
    {
        $csv = $this->createCsv(self::VALID_CSV_FILE);
        $product = $this->createValidProduct();
        $iterator = new ArrayIterator([$product]);

        $this->csvReaderFactory
            ->expects($this->once())
            ->method('create')
            ->with($csv)
            ->willReturn($this->createMockReaderWithHeaderSyntaxErrorAndNoRecord($iterator));

        $this->expectException(CsvReaderInvalidHeadersException::class);
        $this->expectExceptionMessage(self::SYNTAX_ERROR_EXCEPTION_MESSAGE);

        $this->csvProcessor->getRecords($csv);
    }

    /**
     * @throws Exception
     */
    public function testGetRecordsWithCsvReaderException(): void
    {
        $csv = $this->createCsv(self::VALID_CSV_FILE);
        $product = $this->createValidProduct();
        $iterator = new ArrayIterator([$product]);

        $this->csvReaderFactory
            ->expects($this->once())
            ->method('create')
            ->with($csv)
            ->willReturn($this->createMockReaderWithValidHeadersAndRecordsException($iterator));

        $this->expectException(CsvReaderException::class);
        $this->expectExceptionMessage(self::CSV_READER_RUNTIME_EXCEPTION_MESSAGE);

        $this->csvProcessor->getRecords($csv);
    }

    public function testProcessRecordsWithValidCsv(): void
    {
        $product = $this->createValidProduct();
        $iterator = new ArrayIterator([$product]);

        $result = $this->csvProcessor->processRecords($iterator);

        $this->validator
            ->expects($this->never())
            ->method('validate');

        $this->assertTrue($result);
    }

    /**
     * @throws Exception
     */
    public function testProcessRecordsWithInvalidCsv(): void
    {
        $product = $this->createInvalidProduct();
        $iterator = new ArrayIterator([$product]);
        $errors[1] = $this->createMockViolationListWithMessageError();;
        $expectedException = new CsvRecordsUnSuccessfulProcessingException([$errors]);

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->willThrowException($expectedException);

        $this->expectException(CsvRecordsUnSuccessfulProcessingException::class);
        $this->expectExceptionMessage('CSV was NOT processed successfully. Here is the list of errors: ');
        $this->expectExceptionObject($expectedException);

        $this->csvProcessor->processRecords($iterator);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->csvReaderFactory = $this->createMock(CsvReaderFactory::class);

        $this->csvProcessor = new CsvProcessor($this->validator, $this->csvReaderFactory);
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

    private function createInvalidProduct(): Product
    {
        return new Product(
            'Product1',
            '"Product Sample 1"',
            'Physical2',
            'TY-1',
            -53,
            354,
            3,
            '"Lorem Ipsum is simply dummy text of the printing and typesetting industry. ID = 1. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum."',
            0.1,
            4.6,
            2.2,
            false
        );
    }

    /**
     * @throws Exception
     */
    private function createMockReaderWithValidHeadersAndRecords(Iterator $iterator): Reader
    {
        $reader = $this->getMockReader();

        $reader
            ->expects($this->once())
            ->method('getRecordsAsObject')
            ->willReturn($iterator);

        $reader
            ->expects($this->once())
            ->method('getHeader')
            ->willReturn(self::VALID_CSV_HEADERS);

        return $reader;
    }

    /**
     * @throws Exception
     */
    private function createMockReaderWithValidHeadersAndRecordsException(): Reader
    {
        $reader = $this->getMockReader();

        $reader
            ->expects($this->once())
            ->method('getRecordsAsObject')
            ->willThrowException(new \League\Csv\Exception(self::CSV_READER_RUNTIME_EXCEPTION_MESSAGE));

        $reader
            ->expects($this->once())
            ->method('getHeader')
            ->willReturn(self::VALID_CSV_HEADERS);

        return $reader;
    }

    /**
     * @throws Exception
     */
    private function createMockReaderWithInvalidHeadersAndNoRecords(Iterator $iterator): Reader
    {
        $reader = $this->getMockReader();

        $reader
            ->expects($this->never())
            ->method('getRecordsAsObject')
            ->willReturn($iterator);

        $reader
            ->expects($this->once())
            ->method('getHeader')
            ->willReturn(self::INVALID_CSV_HEADERS);

        return $reader;
    }

    /**
     * @throws Exception
     */
    private function createMockReaderWithHeaderSyntaxErrorAndNoRecord(Iterator $iterator): Reader
    {
        $reader = $this->getMockReader();

        $reader
            ->expects($this->never())
            ->method('getRecordsAsObject')
            ->willReturn($iterator);

        $reader
            ->expects($this->once())
            ->method('getHeader')
            ->willThrowException(new SyntaxError(self::SYNTAX_ERROR_EXCEPTION_MESSAGE));

        return $reader;
    }

    private function getMockReader(): Reader
    {
        return $this->createMock(Reader::class);
    }

    /**
     * @throws Exception
     */
    private function createMockViolationList(): ConstraintViolationListInterface
    {
        return $this->createMock(ConstraintViolationListInterface::class);
    }

    /**
     * @throws Exception
     */
    private function createMockViolationListWithMessageError(): MockBuilder
    {
        $violationList = $this->getMockBuilder(ConstraintViolationListInterface::class);
        $violationList
            ->disableOriginalConstructor()
            ->setConstructorArgs([
                self::VIOLATION_ERROR_MESSAGE
            ])
            ->getMock();

        return $violationList;
    }

    /**
     * @throws Exception
     */
    private function createMockViolationListWithErrors(): ConstraintViolationListInterface
    {
        $list = $this->createMock(ConstraintViolationListInterface::class);
        $list
            ->expects($this->once())
            ->method('count')
            ->willReturn(1);

        return $list;
    }
}
