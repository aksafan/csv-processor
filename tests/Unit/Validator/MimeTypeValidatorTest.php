<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validator;

use App\Entity\Csv\CsvMimeType;
use App\Validator\MimeType;
use App\Validator\MimeTypeValidator;
use SplFileInfo;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class MimeTypeValidatorTest extends ConstraintValidatorTestCase
{
    private const VALID_CSV_FILE = __DIR__ . '/../Fixtures/valid.csv';
    private const INVALID_CSV_FILE = __DIR__ . '/../Fixtures/invalid.csv';

    private const FORMATTED_VALID_CSV_MIME_TYPES = '"application/vnd.ms-excel", "application/excel", "application/csv", "application/x-csv", "text/x-comma-separated-values", "text/comma-separated-values", "text/plain", "text/csv", "text/x-csv"';
    private const VALID_CSV_MIME_TYPE = 'application/csv';
    private const INVALID_CSV_MIME_TYPE = 'application/json';
    private const INVALID_CSV_MIME_TYPE_FOR_ERROR = '"application/json"';

    private const ERROR_MESSAGE =
        'The mime type of the file is invalid ({{ type }}). Allowed mime types are {{ types }}.';

    private MimeType $mimeType;

    public function testFileIsValid(): void
    {
        $this->validator->validate(new File(self::VALID_CSV_FILE), $this->mimeType);

        $this->assertNoViolation();
    }

    public function testInvalidConstraintType(): void
    {
        $this->expectException(ConstraintDefinitionException::class);
        $this->expectExceptionMessage(sprintf('Constraint must be an instance of %s.', MimeType::class));
        $this->validator->validate(new File(self::VALID_CSV_FILE), new UniqueEntity([]));
    }

    public function testEmptyMimeTypes(): void
    {
        $this->expectException(ConstraintDefinitionException::class);
        $this->expectExceptionMessage('At least one mime type has to be specified.');
        $this->validator->validate(new File(self::VALID_CSV_FILE), new MimeType([]));
    }

    public function testNullFile(): void
    {
        $this->validator->validate(null, $this->mimeType);

        $this->assertNoViolation();
    }

    public function testEmptyStringFile(): void
    {
        $this->validator->validate('', $this->mimeType);

        $this->assertNoViolation();
    }

    public function testUnexpectedValue(): void
    {
        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage(
            sprintf('Expected argument of type "%s", "%s" given', File::class, SplFileInfo::class)
        );
        $this->validator->validate(new SplFileInfo(self::VALID_CSV_FILE), $this->mimeType);
    }

    public function testFileIsInvalid(): void
    {
        $this->validator->validate(new File(self::INVALID_CSV_FILE), $this->mimeType);

        $this->buildViolation(self::ERROR_MESSAGE)
            ->setParameter('{{ type }}', self::INVALID_CSV_MIME_TYPE_FOR_ERROR)
            ->setParameter('{{ types }}', self::FORMATTED_VALID_CSV_MIME_TYPES)
            ->setCode(\Symfony\Component\Validator\Constraints\File::INVALID_MIME_TYPE_ERROR)
            ->assertRaised();
    }

    public function testWithValidStringMimeType(): void
    {
        $file = $this->createMock(File::class);
        $file->expects($this->once())
            ->method('getMimeType')
            ->willReturn(CsvMimeType::DEFAULT_TYPES[0]);

        $this->validator->validate($file, new MimeType(CsvMimeType::DEFAULT_TYPES[0]));

        $this->assertNoViolation();
    }

    public function testWithInvalidStringMimeType(): void
    {
        $file = $this->createMock(File::class);
        $file->expects($this->once())
            ->method('getMimeType')
            ->willReturn(self::INVALID_CSV_MIME_TYPE);

        $this->validator->validate($file, new MimeType(CsvMimeType::DEFAULT_TYPES[0]));

        $this->buildViolation(self::ERROR_MESSAGE)
            ->setParameter('{{ type }}', self::INVALID_CSV_MIME_TYPE_FOR_ERROR)
            ->setParameter('{{ types }}', sprintf('"%s"', CsvMimeType::DEFAULT_TYPES[0]))
            ->setCode(\Symfony\Component\Validator\Constraints\File::INVALID_MIME_TYPE_ERROR)
            ->assertRaised();
    }

    public function testWithValidArrayMimeType(): void
    {
        $file = $this->createMock(File::class);
        $file->expects($this->once())
            ->method('getMimeType')
            ->willReturn(self::VALID_CSV_MIME_TYPE);

        $this->validator->validate($file, new MimeType(CsvMimeType::DEFAULT_TYPES));

        $this->assertNoViolation();
    }

    public function testWithInvalidArrayMimeType(): void
    {
        $file = $this->createMock(File::class);
        $file->expects($this->once())
            ->method('getMimeType')
            ->willReturn(self::INVALID_CSV_MIME_TYPE);

        $this->validator->validate($file, new MimeType(CsvMimeType::DEFAULT_TYPES));

        $this->buildViolation(self::ERROR_MESSAGE)
            ->setParameter('{{ type }}', self::INVALID_CSV_MIME_TYPE_FOR_ERROR)
            ->setParameter('{{ types }}', self::FORMATTED_VALID_CSV_MIME_TYPES)
            ->setCode(\Symfony\Component\Validator\Constraints\File::INVALID_MIME_TYPE_ERROR)
            ->assertRaised();
    }

    public function testWithEmptyMimeType(): void
    {
        $file = $this->createMock(File::class);
        $file->expects($this->once())
            ->method('getMimeType')
            ->willReturn(null);

        $this->validator->validate($file, new MimeType(CsvMimeType::DEFAULT_TYPES));

        $this->buildViolation(self::ERROR_MESSAGE)
            ->setParameter('{{ type }}', 'null')
            ->setParameter('{{ types }}', self::FORMATTED_VALID_CSV_MIME_TYPES)
            ->setCode(\Symfony\Component\Validator\Constraints\File::INVALID_MIME_TYPE_ERROR)
            ->assertRaised();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->mimeType = new MimeType(CsvMimeType::DEFAULT_TYPES);
    }

    protected function createValidator(): MimeTypeValidator
    {
        return new MimeTypeValidator();
    }
}
