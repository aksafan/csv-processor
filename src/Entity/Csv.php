<?php

declare(strict_types=1);

namespace App\Entity;

use App\Validator as CustomAssert;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

class Csv
{
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

    public function __construct(
        #[Assert\Sequentially([
            new Assert\NotBlank(
                message: 'Please upload a CSV file'
            ),
            new CustomAssert\MimeType(
                mimeTypes: self::CSV_MIME_TYPES
            ),
            new Assert\File(
                maxSize: '256m',
                extensions: ['csv'],
                extensionsMessage: 'Please upload a valid CSV',
            ),
        ])]
        public readonly ?File $csvFile,
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        #[Assert\Length(1)]
        public string $delimiter = CsvProperties::DELIMITER,
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        #[Assert\Length(1)]
        public string $enclosure = CsvProperties::ENCLOSURE,
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        #[Assert\Length(1)]
        public string $escape = CsvProperties::ESCAPE,
    ) {
    }
}
