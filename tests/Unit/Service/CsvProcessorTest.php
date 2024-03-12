<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\CsvScheme\Product;
use App\Entity\Exception\Domain\Reader\CsvRecordsUnSuccessfulProcessingException;
use App\Entity\Exception\Domain\Reader\CsvRecordUnSuccessfulProcessingException;
use App\Service\CsvProcessor;
use App\Service\CsvProcessorInterface;
use App\Service\CsvRecordParserInterface;
use ArrayIterator;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class CsvProcessorTest extends TestCase
{
    private const VIOLATION_ERROR_MESSAGE = 'The mime type of the file is invalid.';

    private CsvProcessorInterface $csvProcessor;

    private CsvRecordParserInterface $csvRecordParser;

    public function testProcessRecordsWithValidCsv(): void
    {
        $product = $this->createValidProduct();
        $iterator = new ArrayIterator([$product]);

        $this->csvRecordParser
            ->method('parse')
            ->willReturn(true);

        $result = $this->csvProcessor->processRecords($iterator);

        $this->assertTrue($result);
    }

    public function testProcessRecordsWithInvalidCsv(): void
    {
        $product = $this->createInvalidProduct();
        $iterator = new ArrayIterator([$product]);
        $errors = $this->createMockViolationListWithMessageError();;
        $expectedException = new CsvRecordsUnSuccessfulProcessingException([$errors]);

        $this->csvRecordParser
            ->method('parse')
            ->willThrowException($expectedException);

        $this->expectException(CsvRecordsUnSuccessfulProcessingException::class);
        $this->expectExceptionMessage('CSV was NOT processed successfully. Here is the list of errors: ');
        $this->expectExceptionObject($expectedException);

        $this->csvProcessor->processRecords($iterator);
    }

    public function testProcessRecordsWithErrors(): void
    {
        $product = $this->createInvalidProduct();
        $iterator = new ArrayIterator([$product]);
        $errors = new ConstraintViolationList($this->createMockViolationListWithMessageError());
        $expectedException = new CsvRecordUnSuccessfulProcessingException($errors);

        $this->csvRecordParser
            ->method('parse')
            ->willThrowException($expectedException);

        $this->expectException(CsvRecordsUnSuccessfulProcessingException::class);
        $this->expectExceptionMessage('CSV was NOT processed successfully. Here is the list of errors: ');

        $this->csvProcessor->processRecords($iterator);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->csvRecordParser = $this->createMock(CsvRecordParserInterface::class);

        $this->csvProcessor = new CsvProcessor(
            $this->csvRecordParser
        );
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

    private function createMockViolationListWithMessageError(): ConstraintViolationListInterface
    {
        $violationList = new ConstraintViolationList();
        $violationList->add(new ConstraintViolation(self::VIOLATION_ERROR_MESSAGE, '', [], null, '', null));

        return $violationList;
    }
}
