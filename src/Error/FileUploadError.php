<?php

declare(strict_types=1);

namespace App\Error;

enum FileUploadError
{
    case FILE_INVALID;
    case FILE_ABSENT;
    case FILE_TOO_BIG;
    case POSSIBLE_FILE_UPLOAD_ATTACK;

    case UPLOAD_INI_SIZE;
    case UPLOAD_FORM_SIZE;
    case UPLOAD_PARTIAL;
    case UPLOAD_NO_FILE;
    case UPLOAD_NO_TMP;
    case UPLOAD_CANT_WRITE;
    case UPLOAD_EXTENSION;
    case UNKNOWN;

    public function message(): string
    {
        return match ($this) {
            self::FILE_INVALID => 'Please upload a valid %s file.',
            self::FILE_ABSENT => 'File is missed.',
            self::FILE_TOO_BIG => 'Please upload a file not greater than %dMB.',
            self::POSSIBLE_FILE_UPLOAD_ATTACK => 'Possible file upload attack with filename: %s',
            self::UPLOAD_INI_SIZE => "The file upload failed as the file exceeds the upload_max_filesize directive in php.ini.",
            self::UPLOAD_FORM_SIZE => "The file upload failed as the file exceeds the MAX_FILE_SIZE directive specified by the form.",
            self::UPLOAD_PARTIAL => "The file upload failed as the file was only partially uploaded.",
            self::UPLOAD_NO_FILE => "The file upload failed as no file was uploaded.",
            self::UPLOAD_NO_TMP => "The file upload failed as the PHP temporary directory does not exist.",
            self::UPLOAD_CANT_WRITE => "The file upload failed as the file cannot be written to the PHP temporary directory.",
            self::UPLOAD_EXTENSION => "The file upload failed a PHP extension caused the upload to stop.",
            self::UNKNOWN => 'Unknown upload error',
        };
    }
}