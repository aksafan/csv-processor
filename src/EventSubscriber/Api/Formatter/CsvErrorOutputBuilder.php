<?php

declare(strict_types=1);

namespace App\EventSubscriber\Api\Formatter;

use Symfony\Component\Validator\ConstraintViolationListInterface;

readonly class CsvErrorOutputBuilder
{
    /**
     * @param ConstraintViolationListInterface[] $errors
     *
     * @return CsvErrorOutput[]
     */
    public function build(array $errors): array
    {
        $csvErrors = [];
        foreach ($errors as $rowIndex => $errorList) {
            $csvErrors[] = new CsvErrorOutput($rowIndex, $errorList);
        }

        return $csvErrors;
    }
}