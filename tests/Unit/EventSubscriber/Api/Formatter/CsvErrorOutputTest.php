<?php

declare(strict_types=1);

namespace App\Tests\Unit\EventSubscriber\Api\Formatter;

use App\EventSubscriber\Api\Formatter\CsvErrorOutput;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;

class CsvErrorOutputTest extends TestCase
{
    public function testCsvErrorOutputInstantiation(): void
    {
        $violationListMock = $this->createMock(ConstraintViolationListInterface::class);

        $rowIndex = 1;
        $csvErrorOutput = new CsvErrorOutput($rowIndex, $violationListMock);

        $this->assertEquals($rowIndex, $csvErrorOutput->rowIndex);
        $this->assertSame($violationListMock, $csvErrorOutput->violationInfo);
    }
}