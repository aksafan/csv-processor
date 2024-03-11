<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Entity\Csv;
use App\EventSubscriber\Api\Formatter\CsvErrorOutputBuilder;
use App\Service\CsvProcessor;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CsvProcessorController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly CsvProcessor $csvProcessorService,
        private readonly CsvErrorOutputBuilder $csvErrorOutputBuilder
    ) {
    }

    #[Route(
        path: 'csv/processor',
        name: 'csv_processor',
        methods: ['POST']
    )]
    public function process(Request $request): JsonResponse
    {
        $csvProcessor = $this->serializer->denormalize($request->getPayload()->all(), Csv::class, null, [
            AbstractNormalizer::OBJECT_TO_POPULATE => new Csv($request->files->get('csv_file')),
            AbstractNormalizer::IGNORED_ATTRIBUTES => ['csvFile'],
        ]);

        $violations = $this->validator->validate($csvProcessor);
        if ($violations->count()) {
            $json = $this->serializer->serialize($this->csvErrorOutputBuilder->build([$violations]), 'json');

            return JsonResponse::fromJsonString($json, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json($this->csvProcessorService->processFacade($csvProcessor), Response::HTTP_OK);
    }

    #[Route(
        path: 'csv/processor/async',
        name: 'csv_processor_async',
        methods: ['POST']
    )]
    public function processAsync(Request $request): JsonResponse
    {
        // TODO: add job pushing to message broker
        return $this->json([]);
    }
}
