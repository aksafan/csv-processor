<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber\Api\Formatter;

use App\EventSubscriber\Api\Formatter\CsvErrorOutput;
use App\EventSubscriber\Api\Formatter\CsvErrorOutputBuilder;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class CsvErrorOutputBuilderTest extends TestCase
{
    public function testBuildCsvErrorOutputs(): void
    {
        $violationListMock = $this->createMock(ConstraintViolationListInterface::class);

        $errors = [
            1 => $violationListMock,
            2 => $violationListMock,
            3 => $violationListMock,
        ];

        $csvErrorOutputBuilder = new CsvErrorOutputBuilder();

        $csvErrorOutputs = $csvErrorOutputBuilder->build($errors);

        $this->assertIsArray($csvErrorOutputs);
        $this->assertCount(count($errors), $csvErrorOutputs);

        foreach ($csvErrorOutputs as $index => $csvErrorOutput) {
            $this->assertInstanceOf(CsvErrorOutput::class, $csvErrorOutput);
            $this->assertEquals($index + 1, $csvErrorOutput->rowIndex);
            $this->assertSame($violationListMock, $csvErrorOutput->violationInfo);
        }
    }
}
