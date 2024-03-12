<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber\Api;

use App\EventSubscriber\Api\ErrorHandler;
use DomainException;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ErrorHandlerTest extends TestCase
{
    private const DOMAIN_EXCEPTION_MESSAGE = 'Domain Exception message';

    public function testHandleLogsWarning(): void
    {
        $loggerMock = $this->createMock(LoggerInterface::class);

        $errorHandler = new ErrorHandler($loggerMock);

        $exception = new DomainException(self::DOMAIN_EXCEPTION_MESSAGE);

        $loggerMock->expects($this->once())
            ->method('warning')
            ->with(self::DOMAIN_EXCEPTION_MESSAGE, ['exception' => $exception]);

        $errorHandler->handle($exception);
    }
}
