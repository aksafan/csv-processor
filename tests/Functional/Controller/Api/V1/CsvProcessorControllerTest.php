<?php

declare(strict_types=1);

namespace App\Tests\Functional\Controller\Api\V1;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;

class CsvProcessorControllerTest extends WebTestCase
{
    private const VALID_CSV_FILE = __DIR__ . '/../../../Fixtures/valid.csv';
    private const INVALID_CSV_FILE = __DIR__ . '/../../../Fixtures/invalid.csv';
    private const VALIDATION_ERROR_JSON_FILE = __DIR__ . '/../../../Fixtures/validation_error_response.json';

    protected KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = static::createClient();
        $this->client->disableReboot();
    }

    public function testProcessValidCsv(): void
    {
        $csvFile = new UploadedFile(self::VALID_CSV_FILE, 'valid.csv');

        $payload = [
            'csv_file' => $csvFile,
        ];

        $this->client->request('POST', '/api/v1/csv/processor', ['Content-Type' => 'multipart/form-data'], $payload);
        $this->assertEquals(Response::HTTP_OK, $this->client->getResponse()->getStatusCode());
        $this->assertJson($this->client->getResponse()->getContent());
        $this->assertEquals('true', $this->client->getResponse()->getContent());
    }

    public function testProcessInvalidCsv(): void
    {
        $csvFile = new UploadedFile(self::INVALID_CSV_FILE, 'invalid.csv');

        $payload = [
            'csv_file' => $csvFile,
        ];

        $this->client->request('POST', '/api/v1/csv/processor', $payload);

        $this->assertEquals(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $this->assertJson($this->client->getResponse()->getContent());
        $this->assertJsonStringEqualsJsonFile(self::VALIDATION_ERROR_JSON_FILE, $this->client->getResponse()->getContent());
    }
}
