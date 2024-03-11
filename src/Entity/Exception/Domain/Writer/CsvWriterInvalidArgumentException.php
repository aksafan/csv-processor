<?php

declare(strict_types=1);

namespace App\Entity\Exception\Domain\Writer;

use App\Entity\Exception\Domain\Reader\CsvReaderException;
use Throwable;

class CsvWriterInvalidArgumentException extends CsvReaderException
{
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct(sprintf('CsvWriter invalid argument error: %s.', $message), $code, $previous);
    }
}