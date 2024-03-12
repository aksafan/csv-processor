<?php

declare(strict_types=1);

namespace App\Entity\Csv;

use App\Validator as CustomAssert;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;

class Csv
{
    public function __construct(
        #[Assert\Sequentially([
            new Assert\NotBlank(
                message: 'Please upload a CSV file'
            ),
            new CustomAssert\MimeType(
                mimeTypes: CsvMimeType::DEFAULT_TYPES
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
