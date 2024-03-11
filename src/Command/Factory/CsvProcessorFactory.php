<?php

declare(strict_types=1);

namespace App\Command\Factory;

use App\Entity\Csv\Csv;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\HttpFoundation\File\File;

final readonly class CsvProcessorFactory
{
    public function createFromInput(InputInterface $input): Csv
    {
        return new Csv(
            new File($input->getArgument('path-to-csv')),
            $input->getOption('delimiter'),
            $input->getOption('enclosure'),
            $input->getOption('escape')
        );
    }
}