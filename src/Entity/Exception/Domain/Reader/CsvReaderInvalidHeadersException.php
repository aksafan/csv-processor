<?php

declare(strict_types=1);

namespace App\Entity\Exception\Domain\Reader;

use Throwable;

class CsvReaderInvalidHeadersException extends CsvReaderException
{
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(sprintf('Headers are invalid: %s.', $message), $code, $previous);
    }
}
