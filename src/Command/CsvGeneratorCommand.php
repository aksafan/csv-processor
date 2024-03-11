<?php

declare(strict_types=1);

namespace App\Command;

use App\Command\Factory\CsvGeneratorFactory;
use App\Entity\CsvGenerator;
use App\Entity\CsvProperties;
use App\Entity\Exception\Domain\Writer\CsvWriterException;
use App\Service\Factory\CsvWriterFactory;
use League\Csv\CannotInsertRecord;
use League\Csv\Exception;
use League\Csv\Writer;
use RuntimeException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsCommand(
    name: 'csv:generate',
    description: 'Generate a CSV file for test purpose.',
)]
class CsvGeneratorCommand extends AbstractCommand
{
    private const CSV_NUMBER_OF_RECORDS_DEFAULT = 100;

    private const DESCRIPTION_SAMPLE = 'Lorem Ipsum is simply dummy text of the printing and typesetting industry. ID = %s. Lorem Ipsum has been the industry\'s standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.';

    private const STOP_WATCH_EVENT_NAME = 'csv_generator_event';

    public function __construct(
        private readonly ValidatorInterface $validator,
        private readonly CsvGeneratorFactory $csvGeneratorFactory,
        private readonly CsvWriterFactory $csvWriterFactory
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument(
                'path-to-csv-folder',
                InputArgument::REQUIRED,
                'Path to where csv should be saved. With ending slash.'
            )
            ->addOption(
                'number-of-records',
                'N',
                InputOption::VALUE_OPTIONAL,
                'Number of records to generate for CSV file.',
                self::CSV_NUMBER_OF_RECORDS_DEFAULT
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

        $csvGenerator = $this->csvGeneratorFactory->createFromInput($input);
        $violations = $this->validator->validate($csvGenerator);
        if ($violations->count()) {
            return $this->endWithFailure($io, $stopwatch, 'CSV generator validation errors:', [$violations]);
        }

        $pathToSaveCsv = $this->getPathToSaveCsv($csvGenerator);

        $io->note(sprintf('Starting to generate CSV file: "%s".', $pathToSaveCsv));

        $outputStream = fopen($pathToSaveCsv, 'w');

        try {
            $csvWriter = $this->csvWriterFactory->create($csvGenerator, $outputStream);
        } catch (CsvWriterException $exception) {
            return $this->endWithFailure($io, $stopwatch, $exception->getMessage());
        } catch (RuntimeException $exception) {
            return $this->endWithFailure($io, $stopwatch, sprintf('Runtime error: %s.', $exception->getMessage()));
        }

        $progressBar = $this->getProgressBar($output, (int)$csvGenerator->numberOfRecords);

        for ($i = 1; $i < $csvGenerator->numberOfRecords; $i++) {
            try {
                $this->insertRecord($i, $csvWriter);
            } catch (CannotInsertRecord $exception) {
                return $this->endWithFailure(
                    $io,
                    $stopwatch,
                    sprintf('CsvProperties generator specific error: %s.', $exception->getMessage())
                );
            } catch (Exception $exception) {
                return $this->endWithFailure(
                    $io,
                    $stopwatch,
                    sprintf('CsvProperties generator error: %s.', $exception->getMessage())
                );
            }
            $progressBar->advance();
        }

        fclose($outputStream);

        return $this->endWithSuccess(
            $io,
            $stopwatch,
            sprintf(
                'CSV "%s" was generated successfully. No errors have been detected.',
                $csvGenerator->pathToCsvFolder
            )
        );
    }

    private function getPathToSaveCsv(CsvGenerator $csvGenerator): string
    {
        return sprintf(
            '%stest_csv_%s_records_%s.csv',
            $csvGenerator->pathToCsvFolder,
            $csvGenerator->numberOfRecords,
            time()
        );
    }

    /**
     * @param int $i
     * @param Writer $csvWriter
     *
     * @return void
     *
     * @throws CannotInsertRecord
     * @throws Exception
     */
    private function insertRecord(int $i, Writer $csvWriter): void
    {
        $data = $this->prepareRecord($i);
        $csvWriter->insertOne($data);
    }

    private function prepareRecord(int $i): array
    {
        $item = sprintf('%s', mt_rand(0, 1) === 0 ? 'Product' : 'Service');

        return [
            $item,
            sprintf('%s Sample %s', $item, $i),
            sprintf('%s', mt_rand(0, 1) === 0 ? 'Physical' : 'Non-Physical'),
            sprintf('TY-%s', $i),
            mt_rand(0, 100),
            mt_rand(0, 1000),
            mt_rand(0, 50),
            sprintf(self::DESCRIPTION_SAMPLE, $i),
            mt_rand(1, 100) / 10,
            mt_rand(1, 50) / 10,
            mt_rand(1, 50) / 10,
            mt_rand(0, 1) === 0 ? 'TRUE' : 'FALSE'
        ];
    }
}
