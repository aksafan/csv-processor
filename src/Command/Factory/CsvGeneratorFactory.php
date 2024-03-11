<?php

declare(strict_types=1);

namespace App\Command\Factory;

use App\Entity\CsvGenerator;
use Symfony\Component\Console\Input\InputInterface;

final readonly class CsvGeneratorFactory
{
    public function createFromInput(InputInterface $input): CsvGenerator
    {
        return new CsvGenerator(
            $input->getArgument('path-to-csv-folder'),
            (int)$input->getOption('number-of-records'),
            $input->getOption('delimiter'),
            $input->getOption('enclosure'),
            $input->getOption('escape')
        );
    }
}