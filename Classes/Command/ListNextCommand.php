<?php

declare(strict_types=1);

namespace SUDHAUS7\Sudhaus7Wizard\Command;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use SUDHAUS7\Sudhaus7Wizard\Service\ProcessService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ListNextCommand extends Command
{
    public function __construct(
        private ProcessService $processService,
        ?string $name = null
    ) {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setDescription('SUDHAUS7 Wizard. Shows list of not executed processes');
    }

    /**
     * @throws Exception
     * @throws DBALException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->processService->printList($output);

        return Command::SUCCESS;
    }
}
