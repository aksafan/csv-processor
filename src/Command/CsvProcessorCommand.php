<?php

declare(strict_types=1);

namespace App\Command;

use App\Command\Factory\CsvProcessorFactory;
use App\Entity\CsvProperties;
use App\Entity\Exception\Domain\Reader\CsvReaderException;
use App\Entity\Exception\Domain\Reader\CsvRecordUnSuccessfulProcessingException;
use App\Service\CsvProcessor;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'csv:process',
    description: 'Processes a CSV file and validates it.',
)]
class CsvProcessorCommand extends AbstractCommand
{
    protected const STOP_WATCH_EVENT_NAME = 'csv_processor_event';

    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly CsvProcessorFactory $csvProcessorFactory,
        private readonly CsvProcessor $csvProcessorService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'path-to-csv',
                InputArgument::REQUIRED,
                'Path to csv you want to process and validate.'
            )
            ->addOption(
                'delimiter',
                'D',
                InputOption::VALUE_OPTIONAL,
                'Delimiter used inside CSV file.',
                CsvProperties::DELIMITER
            )
            ->addOption(
                'enclosure',
                'ENC',
                InputOption::VALUE_OPTIONAL,
                'Enclosure character used inside CSV file.',
                CsvProperties::ENCLOSURE
            )
            ->addOption(
                'escape',
                'ESC',
                InputOption::VALUE_OPTIONAL,
                'Escape character used inside CSV file.',
                CsvProperties::ESCAPE
            );
    }

    protected function getStopWatchEvenName(): string
    {
        return self::STOP_WATCH_EVENT_NAME;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $stopwatch = $this->getStopwatch();
        $io = new SymfonyStyle($input, $output);
        $errors = [];

        $csvProcessor = $this->csvProcessorFactory->createFromInput($input);
        $violations = $this->validator->validate($csvProcessor);
        if ($violations->count()) {
            return $this->endWithFailure($io, $stopwatch, 'CSV file validation errors:', [$violations]);
        }

        $io->note(sprintf('Starting to process file: "%s".', $csvProcessor->csvFile->getPathname()));

        try {
            $records = $this->csvProcessorService->getRecords($csvProcessor);
        } catch (CsvReaderException $exception) {
            return $this->endWithFailure($io, $stopwatch, $exception->getMessage());
        } catch (RuntimeException $exception) {
            return $this->endWithFailure($io, $stopwatch, sprintf('Runtime error: %s.', $exception->getMessage()));
        }

        $progressBar = $this->getProgressBar($output, iterator_count($records));

        foreach ($records as $record) {
            try {
                $this->csvProcessorService->processRecord($record);
            } catch (CsvRecordUnSuccessfulProcessingException $exception) {
                $errors[$records->key() + 1] = $exception->errors;
            }
            $progressBar->advance();
        }

        if ($errors) {
            return $this->endWithFailure(
                $io,
                $stopwatch,
                sprintf(
                    'CSV "%s" was NOT processed successfully. Here is the list of errors: ',
                    $csvProcessor->csvFile->getPathname()
                ),
                $errors
            );
        }

        return $this->endWithSuccess(
            $io,
            $stopwatch,
            sprintf(
                'CSV "%s" was processed successfully. No errors have been detected.',
                $csvProcessor->csvFile->getPathname()
            )
        );
    }
}
