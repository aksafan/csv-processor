<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber\Api\Formatter;

use App\EventSubscriber\Api\Formatter\CsvErrorOutput;
use App\EventSubscriber\Api\Formatter\CsvErrorOutputBuilder;
use App\EventSubscriber\Api\Formatter\DomainExceptionFormatter;
use App\EventSubscriber\Api\ErrorHandler;
use App\Entity\Exception\Domain\Reader\CsvRecordsUnSuccessfulProcessingException;
use DomainException;
use Exception;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolationList;

class DomainExceptionFormatterTest extends TestCase
{
    private const DOMAIN_EXCEPTION_MESSAGE = 'Domain Exception message.';

    public function testOnKernelExceptionHandlesCsvRecordsUnSuccessfulProcessingException(): void
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $errorsMock = new ErrorHandler($loggerMock);
        $serializerMock = $this->createMock(SerializerInterface::class);
        $csvErrorOutputBuilderMock = new CsvErrorOutputBuilder();

        $domainExceptionFormatter = new DomainExceptionFormatter(
            $errorsMock,
            $serializerMock,
            $csvErrorOutputBuilderMock
        );

        $errors[1] = $this->createViolationList();
        $exception = new CsvRecordsUnSuccessfulProcessingException($errors);
        $request = new Request();
        $request->attributes->set('_route', 'api_v1_csv_processor');
        $event = new ExceptionEvent($this->createMock(HttpKernelInterface::class), $request, 1, $exception);

        $serializerMock->expects($this->once())
            ->method('serialize')
            ->with([new CsvErrorOutput(1, $this->createViolationList())], JsonEncoder::FORMAT)
            ->willReturn('{}');

        $domainExceptionFormatter->onKernelException($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $response->getStatusCode());
        $this->assertJsonStringEqualsJsonString('{}', $response->getContent());
    }

    public function testOnKernelExceptionNotDomainException(): void
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $errorsMock = new ErrorHandler($loggerMock);
        $serializerMock = $this->createMock(SerializerInterface::class);
        $csvErrorOutputBuilderMock = new CsvErrorOutputBuilder();

        $domainExceptionFormatter = new DomainExceptionFormatter(
            $errorsMock,
            $serializerMock,
            $csvErrorOutputBuilderMock
        );

        $exception = new Exception();
        $request = new Request();
        $request->attributes->set('_route', 'api_v1_csv_processor');
        $event = new ExceptionEvent($this->createMock(HttpKernelInterface::class), $request, 1, $exception);

        $serializerMock->expects($this->never())
            ->method('serialize');

        $domainExceptionFormatter->onKernelException($event);
    }

    public function testOnKernelExceptionWrongRoute(): void
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $errorsMock = new ErrorHandler($loggerMock);
        $serializerMock = $this->createMock(SerializerInterface::class);
        $csvErrorOutputBuilderMock = new CsvErrorOutputBuilder();

        $domainExceptionFormatter = new DomainExceptionFormatter(
            $errorsMock,
            $serializerMock,
            $csvErrorOutputBuilderMock
        );

        $errors[1] = $this->createViolationList();
        $exception = new CsvRecordsUnSuccessfulProcessingException($errors);
        $request = new Request();
        $request->attributes->set('_route', 'csv_processor');
        $event = new ExceptionEvent($this->createMock(HttpKernelInterface::class), $request, 1, $exception);

        $serializerMock->expects($this->never())
            ->method('serialize');

        $domainExceptionFormatter->onKernelException($event);
    }

    public function testOnKernelExceptionHandlesDomainToBadRequestException(): void
    {
        $loggerMock = $this->createMock(LoggerInterface::class);
        $errorsMock = new ErrorHandler($loggerMock);
        $serializerMock = $this->createMock(SerializerInterface::class);
        $csvErrorOutputBuilderMock = new CsvErrorOutputBuilder();

        $domainExceptionFormatter = new DomainExceptionFormatter(
            $errorsMock,
            $serializerMock,
            $csvErrorOutputBuilderMock
        );

        $exception = new DomainException(self::DOMAIN_EXCEPTION_MESSAGE);
        $request = new Request();
        $request->attributes->set('_route', 'api_v1_csv_processor');
        $event = new ExceptionEvent($this->createMock(HttpKernelInterface::class), $request, 1, $exception);

        $serializerMock->expects($this->never())
            ->method('serialize');

        $domainExceptionFormatter->onKernelException($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(Response::HTTP_BAD_REQUEST, $response->getStatusCode());
        $jsonResponse = [
            'error' => [
                'code' => Response::HTTP_BAD_REQUEST,
                'message' => self::DOMAIN_EXCEPTION_MESSAGE,
            ],
        ];
        $this->assertJsonStringEqualsJsonString(json_encode($jsonResponse), $response->getContent());
    }

    public function testGetSubscribedEvents(): void
    {
        $expectedSubscribedEvents = [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];

        $actualSubscribedEvents = DomainExceptionFormatter::getSubscribedEvents();

        $this->assertEquals($expectedSubscribedEvents, $actualSubscribedEvents);
    }

    private function createViolationList(): ConstraintViolationList
    {
         return new ConstraintViolationList();
    }
}