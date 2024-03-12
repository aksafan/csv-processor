<?php

declare(strict_types=1);

namespace App\Entity\Exception\Domain\Reader;

use Symfony\Component\Validator\ConstraintViolationListInterface;
use Throwable;

class CsvRecordUnSuccessfulProcessingException extends CsvReaderException
{
    public function __construct(
        public readonly ConstraintViolationListInterface $errors,
        string $message = 'CSV was NOT processed successfully. Here is the list of errors: ',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct(
            $message,
            $code,
            $previous
        );
    }
}
