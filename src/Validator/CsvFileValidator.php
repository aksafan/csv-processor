<?php

declare(strict_types=1);

namespace App\Validator;

use App\Error\FileUploadError;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Symfony\Component\Mime\MimeTypes;

class CsvFileValidator
{
    public const CSV_FILE_EXTENSION = 'csv';
    public const FILE_LIMIT_MB = 64;
    public const BYTES_IN_MB = 1024;

    /**
     * @var string[] Grabbed from https://stackoverflow.com/a/42140178
     */
    private const CSV_MIME_TYPES = [
        'application/vnd.ms-excel',
        'application/excel',
        'application/csv',
        'application/x-csv',
        'text/x-comma-separated-values',
        'text/comma-separated-values',
        'text/plain',
        'text/csv',
        'text/x-csv',
    ];

    private LoggerInterface $logger;
    private MimeTypes $mimeTypes;

    public function __construct(
        LoggerInterface $logger,
        MimeTypes       $mimeTypes
    )
    {
        $this->logger = $logger;
        $this->mimeTypes = $mimeTypes;
    }

    public function validate(array $file): void
    {
        if (!empty($file['error']) && (int)$file['error'] !== UPLOAD_ERR_OK) {
            throw new UnprocessableEntityHttpException($this->getUploadErrorMessage($file['error']));
        }

        if (empty($file['name']) || empty($file['tmp_name'])) {
            throw new UnprocessableEntityHttpException(FileUploadError::FILE_ABSENT->message());
        }
        $filePath = $file['name'];
        $tmpFilePath = $file['tmp_name'];

        if (!$this->isUploadedFile($tmpFilePath)) {
            $this->logger->critical(sprintf(FileUploadError::POSSIBLE_FILE_UPLOAD_ATTACK->message(), $tmpFilePath));

            throw new UnprocessableEntityHttpException(FileUploadError::UNKNOWN->message());
        }

        if (!$this->isFileValid($filePath, $tmpFilePath)) {
            throw new UnprocessableEntityHttpException(sprintf(FileUploadError::FILE_INVALID->message(), self::CSV_FILE_EXTENSION));
        }

        if (!$this->isFileSizeValid($filePath)) {
            throw new UnprocessableEntityHttpException(sprintf(FileUploadError::FILE_TOO_BIG->message(), self::FILE_LIMIT_MB));
        }
    }

    /**
     * Checks whether the file was uploaded via HTTP POST.
     * Used in order to prevent file upload attack.
     *
     * @link http://php.net/manual/en/function.is-uploaded-file.php
     *
     * @param string $filename The filename being checked.
     *
     * @return bool true on success or false on failure.
     */
    protected function isUploadedFile(string $filename): bool
    {
        return is_uploaded_file($filename);
    }

    protected function isFileValid(string $filePath, string $tmpFilePath): bool
    {
        return $this->isMimeTypeValid($tmpFilePath) && $this->isExtensionValid($filePath);
    }

    /**
     * Checks if uploaded file is valid csv by checking file mime type.
     * Mime type guessing works through standard Symfony MimeType guesser.
     * The list of valid and appropriate mime type for each file is formed by this file https://github.com/symfony/symfony/blob/6.1/src/Symfony/Component/Mime/Resources/bin/update_mime_types.php
     * It uses 2 main sources https://cdn.jsdelivr.net/gh/jshttp/mime-db@v1.49.0/db.json and https://gitlab.freedesktop.org/xdg/shared-mime-info/-/raw/master/data/freedesktop.org.xml.in
     *
     * @param string $tmpFilePath
     *
     * @return bool
     */
    protected function isMimeTypeValid(string $tmpFilePath): bool
    {
        $mimeType = $this->mimeTypes->guessMimeType($tmpFilePath);

        return $mimeType && in_array($mimeType, self::CSV_MIME_TYPES, true);
    }

    protected function isExtensionValid(string $filePath): bool
    {
        return strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === self::CSV_FILE_EXTENSION;
    }

    protected function isFileSizeValid(string $filePath): bool
    {
        return filesize($filePath) <= $this->getMaxUploadSize();
    }

    protected function getMaxUploadSize(): int
    {
        return self::FILE_LIMIT_MB * self::BYTES_IN_MB;
    }

    /**
     * Returns a friendly error message according to file upload error.
     *
     * @see https://www.php.net/manual/en/features.file-upload.errors.php
     *
     * @param int $error The error ID from $_FILES['file']['error']
     *
     * @return string Friendly error message
     */
    protected function getUploadErrorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE => FileUploadError::UPLOAD_INI_SIZE->message(),
            UPLOAD_ERR_FORM_SIZE => FileUploadError::UPLOAD_FORM_SIZE->message(),
            UPLOAD_ERR_PARTIAL => FileUploadError::UPLOAD_PARTIAL->message(),
            UPLOAD_ERR_NO_FILE => FileUploadError::UPLOAD_NO_FILE->message(),
            UPLOAD_ERR_NO_TMP_DIR => FileUploadError::UPLOAD_NO_TMP->message(),
            UPLOAD_ERR_CANT_WRITE => FileUploadError::UPLOAD_CANT_WRITE->message(),
            UPLOAD_ERR_EXTENSION => FileUploadError::UPLOAD_EXTENSION->message(),
            default => FileUploadError::UNKNOWN->message(),
        };
    }
}
