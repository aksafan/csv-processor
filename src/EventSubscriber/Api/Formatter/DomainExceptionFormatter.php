<?php

declare(strict_types=1);

namespace App\EventSubscriber\Api\Formatter;

use App\Entity\Exception\Domain\Reader\CsvRecordsUnSuccessfulProcessingException;
use App\EventSubscriber\Api\ErrorHandler;
use DomainException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;

final readonly class DomainExceptionFormatter implements EventSubscriberInterface
{
    public function __construct(
        private ErrorHandler $errors,
        private SerializerInterface $serializer,
        private CsvErrorOutputBuilder $csvErrorOutputBuilder,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    /**
     * Catches DomainExceptions and wraps them in proper error format and response code.
     *
     * @param ExceptionEvent $event
     * @return void
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        $request = $event->getRequest();

        if (!$exception instanceof DomainException) {
            return;
        }

        if (!str_starts_with($request->attributes->get('_route'), 'api_v1_')) {
            return;
        }

        // Handles a group of validation errors for a single CSV record
        if ($exception instanceof CsvRecordsUnSuccessfulProcessingException) {
            $json = $this->serializer->serialize(
                $this->csvErrorOutputBuilder->build($exception->errors),
                JsonEncoder::FORMAT
            );
            $event->setResponse(JsonResponse::fromJsonString($json, Response::HTTP_UNPROCESSABLE_ENTITY));

            return;
        }

        $this->errors->handle($exception);

        $event->setResponse(
            new JsonResponse(
                [
                    'error' => [
                        'code' => Response::HTTP_BAD_REQUEST,
                        'message' => $exception->getMessage(),
                    ],
                ],
                Response::HTTP_BAD_REQUEST,
            )
        );
    }
}
