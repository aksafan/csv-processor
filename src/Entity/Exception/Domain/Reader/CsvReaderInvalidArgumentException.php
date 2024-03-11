<?php

declare(strict_types=1);

namespace App\Entity\Exception\Domain\Reader;

use Throwable;

class CsvReaderInvalidArgumentException extends CsvReaderException
{
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(sprintf('CsvReader invalid argument error: %s.', $message), $code, $previous);
    }
}