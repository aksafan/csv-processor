<?php

declare(strict_types=1);

namespace App\Entity\Exception\Domain\Writer;

use DomainException;
use Throwable;

class CsvWriterException extends DomainException
{
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(sprintf('CsvWriter error: %s.', $message), $code, $previous);
    }
}
