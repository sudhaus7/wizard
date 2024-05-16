<?php

declare(strict_types=1);

namespace SUDHAUS7\Sudhaus7Wizard\Command;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use SUDHAUS7\Sudhaus7Wizard\Domain\Dto\PrepareProcess;
use SUDHAUS7\Sudhaus7Wizard\Domain\Model\Creator;
use SUDHAUS7\Sudhaus7Wizard\Domain\Repository\CreatorRepository;
use SUDHAUS7\Sudhaus7Wizard\Service\ProcessService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class RunWizardCommand extends Command
{
    public function __construct(
        private CreatorRepository $creatorRepository,
        private ConsoleLogger $logger,
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
            new InputOption(
                'mapto',
                'm',
                InputOption::VALUE_OPTIONAL,
                'If set, write page and content mapping CSV to the specific folder',
                null
            ),
        ])
            ->setDescription('SUDHAUS7 Wizard. Helps you to create new pages from a given template. If no options are given, executes the next task in pipeline.');
    }
    protected function initialize(
        InputInterface $input,
        OutputInterface $output
    ): void {
        parent::initialize($input, $output);
    }

    /**
     * @throws Exception
     * @throws DBALException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ioHelper = new SymfonyStyle($input, $output);
        if ($this->creatorRepository->isRunning()) {
            $ioHelper->warning('Wizard has currently running processes');
            return Command::FAILURE;
        }
        $taskId = $input->getOption('id');
        $forceExecution = $input->getOption('force');
        $mappingFolder = $input->getOption('mapto');
        $cleanedUpMappingFolderPath = GeneralUtility::getFileAbsFileName($mappingFolder);
        if ($mappingFolder !== '' && $cleanedUpMappingFolderPath === '') {
            $ioHelper->error('You defined an invalid mapping folder path. Please check and try again.');
            return Command::FAILURE;
        }
        if ($taskId !== null) {
            $creator = $this->creatorRepository->findByIdentifier($taskId, (bool)$forceExecution);
        } else {
            $creator = $this->creatorRepository->findNext();
        }
        if (!$creator instanceof Creator) {
            $ioHelper->info('🎆 No wizard jobs to process 🎆');
            return Command::SUCCESS;
        }

        Bootstrap::initializeBackendAuthentication();
        $process = new PrepareProcess($creator, $this->logger, $cleanedUpMappingFolderPath);

        if ($this->processService->create($process, $ioHelper) === Command::SUCCESS) {
            return Command::SUCCESS;
        }
        return Command::FAILURE;
    }
}
