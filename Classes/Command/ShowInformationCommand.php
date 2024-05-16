<?php

declare(strict_types=1);

namespace SUDHAUS7\Sudhaus7Wizard\Command;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use SUDHAUS7\Sudhaus7Wizard\Domain\Model\Creator;
use SUDHAUS7\Sudhaus7Wizard\Domain\Repository\CreatorRepository;
use SUDHAUS7\Sudhaus7Wizard\Service\ProcessService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class ShowInformationCommand extends Command
{
    public function __construct(
        private CreatorRepository $creatorRepository,
        private ProcessService $processService,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setDefinition([
            new InputOption(
                'id',
                'i',
                InputOption::VALUE_OPTIONAL,
                'ID for executing specific single wizard task',
                null
            ),
            new InputOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'force execution of specific task, works only with --id'
            ),
        ])
            ->setDescription('SUDHAUS7 Wizard. Shows information about single selected or next tasks');
    }

    /**
     * @throws Exception
     * @throws DBALException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $taskId = $input->getOption('id');
        $forceExecution = $input->getOption('force');
        if ($taskId !== null) {
            $creator = $this->creatorRepository->findByIdentifier($taskId, (bool)$forceExecution);
        } else {
            $creator = $this->creatorRepository->findNext();
        }

        if (!$creator instanceof Creator) {
            $output->writeln('<info>No task found. Either none to execute or no id selected.</info>');
            return Command::SUCCESS;
        }

        $this->processService->printInformation($creator, $output);

        return Command::SUCCESS;
    }
}
