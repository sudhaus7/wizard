<?php

/*
 * This file is part of the TYPO3 project.
 *
 * @author Frank Berger <fberger@sudhaus7.de>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace SUDHAUS7\Sudhaus7Wizard\Cli;

use SUDHAUS7\Sudhaus7Wizard\Tools;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @deprecated
 */
final class RunCommand extends Command
{
    protected function configure(): void
    {
        $this->setDescription('Baukasten Wizard');
        $this->setHelp('vendor/bin/typo3 sudhaus7:wizard status');
        $this->addArgument('mode', InputArgument::REQUIRED, 'The mode, either status, list, next or single');
        $this->addOption('id', 'i', InputOption::VALUE_REQUIRED, 'in mode single, the uid of a specific task');
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'force running the task');
        $this->addOption('mapto', 'm', InputOption::VALUE_REQUIRED, 'write the map to this folder');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->getStatus($input, $output);
        return 0;
    }

    public function getStatus(InputInterface $input, OutputInterface $output): void
    {
        $output->writeln([
            '-------------------------------------',
            print_r(Tools::getRegisteredExtensions(), true),
            print_r(Tools::getCreatorConfig(), true),

        ], $output::VERBOSITY_NORMAL);
    }
}
