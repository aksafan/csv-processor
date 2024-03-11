<?php

declare(strict_types=1);

namespace App\Entity\Csv;

use Symfony\Component\Validator\Constraints as Assert;

final class CsvGenerator
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Type('string')]
        public readonly ?string $pathToCsvFolder,
        #[Assert\NotBlank]
        #[Assert\Type('int')]
        #[Assert\LessThanOrEqual(3000000)]
        public readonly ?int $numberOfRecords,
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
