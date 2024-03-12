<?php

declare(strict_types=1);

namespace App\Entity\Exception\Domain\Reader;

use DomainException;
use Throwable;

class CsvReaderException extends DomainException
{
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(sprintf('CsvReader error: %s.', $message), $code, $previous);
    }
}
