<?php

declare(strict_types=1);

namespace App\Tests\Unit\Validator;

use App\Error\FileUploadError;
use App\Validator\CsvFileValidator;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Mime\MimeTypes;

class CsvFileValidatorTest extends TestCase
{
    private const VALID_CSV_FILE = __DIR__ . '/../Fixtures/valid.csv';
    private const INVALID_CSV_FILE = __DIR__ . '/../Fixtures/invalid.txt';

    private CsvFileValidator $validator;
    private LoggerInterface $logger;

    /**
     * @throws Exception
     */
    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->validator = new CsvFileValidator($this->logger, new MimeTypes());
    }

    public function testValidateValidCsvFile(): void
    {
        $this->validator = $this->getMockBuilder(CsvFileValidator::class)
            ->onlyMethods(['isUploadedFile'])
            ->setConstructorArgs([$this->logger, new MimeTypes()])
            ->getMock();
        // Mocking the `isUploadedFile` method to always return true
        // as it checks whether the file was uploaded via HTTP POST.
        $this->validator->expects($this->once())
            ->method('isUploadedFile')
            ->willReturn(true);

        $file = new UploadedFile(self::VALID_CSV_FILE, 'valid.csv');

        // Expecting no exceptions to be thrown
        $this->validator->validate(['name' => $file->getClientOriginalName(), 'tmp_name' => $file->getPathname()]);
    }

    public function testValidateInvalidCsvFile(): void
    {
        $this->validator = $this->getMockBuilder(CsvFileValidator::class)
            ->onlyMethods(['isUploadedFile'])
            ->setConstructorArgs([$this->logger, new MimeTypes()])
            ->getMock();
        // Mocking the `isUploadedFile` method to always return true
        // as it checks whether the file was uploaded via HTTP POST.
        $this->validator->expects($this->once())
            ->method('isUploadedFile')
            ->willReturn(true);

        $file = new UploadedFile(self::INVALID_CSV_FILE, 'invalid.txt');

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage(
            sprintf(FileUploadError::FILE_INVALID->message(), CsvFileValidator::CSV_FILE_EXTENSION)
        );

        $this->validator->validate(['name' => $file->getClientOriginalName(), 'tmp_name' => $file->getPathname()]);
    }

    public function testValidatePossibleFileUploadAttack(): void
    {
        $this->validator = $this->getMockBuilder(CsvFileValidator::class)
            ->onlyMethods(['isUploadedFile'])
            ->setConstructorArgs([$this->logger, new MimeTypes()])
            ->getMock();

        $this->validator->expects($this->once())
            ->method('isUploadedFile')
            ->willReturn(false);

        $file = ['name' => 'test.csv', 'tmp_name' => 'path/to/temp/file'];

        $this->logger->expects($this->once())
            ->method('critical');

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage(FileUploadError::UNKNOWN->message());

        $this->validator->validate($file);
    }

    public function testValidateFileTooBig(): void
    {
        $this->validator = $this->getMockBuilder(CsvFileValidator::class)
            ->onlyMethods(['isUploadedFile', 'isFileValid', 'isFileSizeValid'])
            ->setConstructorArgs([$this->logger, new MimeTypes()])
            ->getMock();
        // Mocking the `isUploadedFile` method to always return true
        // as it checks whether the file was uploaded via HTTP POST.
        $this->validator->expects($this->once())
            ->method('isUploadedFile')
            ->willReturn(true);

        $this->validator->expects($this->once())
            ->method('isFileValid')
            ->willReturn(true);

        $this->validator->expects($this->once())
            ->method('isFileSizeValid')
            ->willReturn(false);

        $file = ['name' => 'test.csv', 'tmp_name' => 'path/to/temp/file'];

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage(sprintf(FileUploadError::FILE_TOO_BIG->message(), CsvFileValidator::FILE_LIMIT_MB));

        $this->validator->validate($file);
    }

    public function testValidateEmptyFileName(): void
    {
        $file = ['name' => '', 'tmp_name' => 'path/to/temp/file'];

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage(FileUploadError::FILE_ABSENT->message());

        $this->validator->validate($file);
    }

    public function testValidateEmptyTmpFileName(): void
    {
        $file = ['name' => 'test.csv', 'tmp_name' => ''];

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage(FileUploadError::FILE_ABSENT->message());

        $this->validator->validate($file);
    }

    public function testValidateIniSizeError(): void
    {
        $file = ['error' => UPLOAD_ERR_INI_SIZE];

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage(FileUploadError::UPLOAD_INI_SIZE->message());

        $this->validator->validate($file);
    }

    public function testValidateFormSizeError(): void
    {
        $file = ['error' => UPLOAD_ERR_FORM_SIZE];

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage(FileUploadError::UPLOAD_FORM_SIZE->message());

        $this->validator->validate($file);
    }

    public function testValidatePartialError(): void
    {
        $file = ['error' => UPLOAD_ERR_PARTIAL];

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage(FileUploadError::UPLOAD_PARTIAL->message());

        $this->validator->validate($file);
    }

    public function testValidateNoFileError(): void
    {
        $file = ['error' => UPLOAD_ERR_NO_FILE];

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage(FileUploadError::UPLOAD_NO_FILE->message());

        $this->validator->validate($file);
    }

    public function testValidateNoTmpDirError(): void
    {
        $file = ['error' => UPLOAD_ERR_NO_TMP_DIR];

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage(FileUploadError::UPLOAD_NO_TMP->message());

        $this->validator->validate($file);
    }

    public function testValidateCantWriteError(): void
    {
        $file = ['error' => UPLOAD_ERR_CANT_WRITE];

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage(FileUploadError::UPLOAD_CANT_WRITE->message());

        $this->validator->validate($file);
    }

    public function testValidateExtensionError(): void
    {
        $file = ['error' => UPLOAD_ERR_EXTENSION];

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage(FileUploadError::UPLOAD_EXTENSION->message());

        $this->validator->validate($file);
    }

    public function testValidateUnknownError(): void
    {
        $file = ['error' => 42];

        $this->expectException(UnprocessableEntityHttpException::class);
        $this->expectExceptionMessage(FileUploadError::UNKNOWN->message());

        $this->validator->validate($file);
    }

    public function testIsFileValid(): void
    {
        $this->validator = $this->getMockBuilder(CsvFileValidator::class)
            ->onlyMethods(['isMimeTypeValid', 'isExtensionValid'])
            ->setConstructorArgs([$this->logger, new MimeTypes()])
            ->getMock();

        $this->validator->expects($this->any())
            ->method('isMimeTypeValid')
            ->willReturn(true);

        $this->validator->expects($this->any())
            ->method('isExtensionValid')
            ->willReturn(true);

        $method = $this->getNonPublicMethod('isFileValid');
        $result = $method->invoke($this->validator, 'test.csv', __FILE__);

        $this->assertTrue($result);
    }

    public function testIsMimeTypeValid(): void
    {
        $method = $this->getNonPublicMethod('isMimeTypeValid');
        $result = $method->invoke($this->validator, self::VALID_CSV_FILE);

        $this->assertTrue($result);
    }

    public function testIsExtensionValid(): void
    {
        $method = $this->getNonPublicMethod('isExtensionValid');
        $result = $method->invoke($this->validator, 'test.csv');

        $this->assertTrue($result);
    }

    public function testIsFileSizeValid(): void
    {
        $method = $this->getNonPublicMethod('isFileSizeValid');
        $result = $method->invoke($this->validator, self::VALID_CSV_FILE);

        $this->assertTrue($result);
    }

    public function testGetMaxUploadSize(): void
    {
        $method = $this->getNonPublicMethod('getMaxUploadSize');
        $result = $method->invoke($this->validator);

        $this->assertSame(CsvFileValidator::FILE_LIMIT_MB * CsvFileValidator::BYTES_IN_MB, $result);
    }

    private function getNonPublicMethod(string $methodName): \ReflectionMethod
    {
        $reflection = new ReflectionClass(CsvFileValidator::class);
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);

        return $method;
    }
}
