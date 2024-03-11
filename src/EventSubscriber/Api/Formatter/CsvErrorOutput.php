<?php

declare(strict_types=1);

namespace App\EventSubscriber\Api\Formatter;

use Symfony\Component\Validator\ConstraintViolationListInterface;

final readonly class CsvErrorOutput
{
    public function __construct(
        public int $rowIndex,
        public ConstraintViolationListInterface $violationInfo
    ) {
    }
}