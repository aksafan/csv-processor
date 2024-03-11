<?php

declare(strict_types=1);

namespace App\EventSubscriber\Api;

use DomainException;
use Psr\Log\LoggerInterface;

final readonly class ErrorHandler
{
    public function __construct(
        private LoggerInterface $logger
    ) {
    }

    public function handle(DomainException $exception): void
    {
        $this->logger->warning($exception->getMessage(), ['exception' => $exception]);
    }
}
