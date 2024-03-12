<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\CsvScheme\Product;
use App\Entity\Exception\Domain\Reader\CsvRecordUnSuccessfulProcessingException;
use App\Service\CsvRecordParser;
use App\Service\CsvRecordParserInterface;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CsvRecordParserTest extends TestCase
{
    private const VIOLATION_ERROR_MESSAGE = 'The mime type of the file is invalid.';

    private ValidatorInterface $validator;

    private CsvRecordParserInterface $csvRecordParser;


    public function testParseRecordWithValidProduct(): void
    {
        $product = $this->createValidProduct();

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->with($product)
            ->willReturn($this->createViolationList());

        $result = $this->csvRecordParser->parse($product);

        $this->assertTrue($result);
    }

    /**
     * @throws Exception
     */
    public function testParseRecordWithInvalidProduct(): void
    {
        $product = $this->createInvalidProduct();
        $errors = $this->createMockViolationListWithMessageError();;
        $expectedException = new CsvRecordUnSuccessfulProcessingException($errors);

        $this->validator
            ->expects($this->once())
            ->method('validate')
            ->with($product)
            ->willReturn($errors);

        $this->expectException(CsvRecordUnSuccessfulProcessingException::class);
        $this->expectExceptionMessage('CSV was NOT processed successfully. Here is the list of errors: ');
        $this->expectExceptionObject($expectedException);

        $this->csvRecordParser->parse($product);
    }

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->csvRecordParser = new CsvRecordParser($this->validator);
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

    private function createViolationList(): ConstraintViolationListInterface
    {
        return new ConstraintViolationList();
    }
}
