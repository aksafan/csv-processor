<?php

declare(strict_types=1);

namespace App\Controller\Api\V1;

use App\Controller\Facade\CsvProcessorFacade;
use App\Entity\Csv\Csv;
use App\EventSubscriber\Api\Formatter\CsvErrorOutputBuilder;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CsvProcessorController extends AbstractController
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
        private readonly CsvErrorOutputBuilder $csvErrorOutputBuilder,
        private readonly CsvProcessorFacade $csvProcessFacade
    ) {
    }

    #[Route(
        path: 'csv/processor',
        name: 'csv_processor',
        methods: ['POST']
    )]
    public function process(Request $request): JsonResponse
    {
        $csv = $this->serializer->denormalize($request->getPayload()->all(), Csv::class, null, [
            AbstractNormalizer::OBJECT_TO_POPULATE => new Csv($request->files->get('csv_file')),
            AbstractNormalizer::IGNORED_ATTRIBUTES => ['csvFile'],
        ]);

        $violations = $this->validator->validate($csv);
        if ($violations->count()) {
            $json = $this->serializer->serialize(
                $this->csvErrorOutputBuilder->build([$violations]),
                JsonEncoder::FORMAT
            );

            return JsonResponse::fromJsonString($json, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        return $this->json($this->csvProcessFacade->process($csv), Response::HTTP_OK);
    }
}
