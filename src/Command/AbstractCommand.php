<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Stopwatch\Stopwatch;
use Symfony\Component\Validator\ConstraintViolationListInterface;

abstract class AbstractCommand extends Command
{
    abstract protected function getStopWatchEvenName(): string;

    protected function getStopwatch(): Stopwatch
    {
        $stopwatch = new Stopwatch();
        $stopwatch->start($this->getStopWatchEvenName());

        return $stopwatch;
    }

    protected function getProgressBar(OutputInterface $output, int $numberOfSteps): ProgressBar
    {
        $section = $output->section();
        $progress = new ProgressBar($section);
        $progress->start($numberOfSteps);

        return $progress;
    }

    protected function endWithSuccess(
        SymfonyStyle $io,
        Stopwatch $stopwatch,
        string $successMessage
    ): int {
        $io->success($successMessage);

        $event = $stopwatch->stop(static::getStopWatchEvenName());
        $io->info((string)$event);

        return Command::SUCCESS;
    }

    protected function endWithFailure(
        SymfonyStyle $io,
        Stopwatch $stopwatch,
        string $failureMessage,
        array $errors = []
    ): int {
        $io->error($failureMessage);
        if ($errors) {
            $this->showErrors($io, $errors);
        }

        $event = $stopwatch->stop(static::getStopWatchEvenName());
        $io->info((string)$event);

        return Command::FAILURE;
    }

    private function showErrors(SymfonyStyle $io, array $errors): void
    {
        foreach ($errors as $recordIndex => $violationList) {
            $io->text(sprintf('Item in line %d:', $recordIndex));
            /** @var ConstraintViolationListInterface $violationList */
            foreach ($violationList as $violation) {
                $errorMessage = sprintf(
                    'Field "%s" has error: %s',
                    $violation->getPropertyPath(),
                    $violation->getMessage()
                );
                $io->text($errorMessage);
            }
            $io->newLine();
        }
    }
}
