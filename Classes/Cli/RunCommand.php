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

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use Psr\Log\LoggerInterface;
use SUDHAUS7\Sudhaus7Wizard\Domain\Model\Creator;
use SUDHAUS7\Sudhaus7Wizard\Domain\Repository\CreatorRepository;
use SUDHAUS7\Sudhaus7Wizard\Logger\DebugConsoleLogger;
use SUDHAUS7\Sudhaus7Wizard\Logger\WizardDatabaseLogger;
use SUDHAUS7\Sudhaus7Wizard\Services\CreateProcessFactoryInterface;
use SUDHAUS7\Sudhaus7Wizard\Tools;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class RunCommand extends Command
{
    public ?LoggerInterface $logger = null;
    private ?CreatorRepository $repository = null;
    private CreateProcessFactoryInterface $createProcessFactory;

    public function __construct(CreateProcessFactoryInterface $createProcessFactory)
    {
        parent::__construct();
        $this->createProcessFactory = $createProcessFactory;
    }

    protected function configure(): void
    {
        $this->setDescription('TYPO3 Baukasten Wizard - Manages and processes wizard-based content creation tasks');
        $this->setHelp(<<<'HELP'
The Baukasten Wizard command allows you to manage and execute wizard creation tasks within TYPO3.

<info>Usage:</info>
  vendor/bin/typo3 sudhaus7:wizard <mode> [options]

<info>Available Modes:</info>
  <comment>status</comment>  Display configuration status including registered extensions and creator config
  <comment>list</comment>    Show all wizard tasks in the queue with their IDs, names, and current status
  <comment>info</comment>    Display detailed information about a specific task (requires --id) or the next pending task
  <comment>next</comment>    Process the next pending wizard task in the queue (if not already running)
  <comment>single</comment>  Execute a specific wizard task by ID (requires --id option)

<info>Options:</info>
  <comment>-i, --id=ID</comment>
      Required for <comment>single</comment> mode, optional for <comment>info</comment> mode.
      Specifies the UID of a specific wizard task to process or display.

  <comment>-f, --force</comment>
      Force execution of a task even if it has already been processed or is marked as running.
      Use with caution as this bypasses normal status checks.

  <comment>-m, --mapto=FOLDER</comment>
      Write the processing map/log output to the specified folder path.
      Useful for debugging and tracking task execution details.

  <comment>--logtodatabase</comment>
      Store detailed execution logs in the database for the current task.
      Logs are linked to the creator record and can be reviewed later.

  <comment>--debug</comment>
      Enable debug mode to print detailed runtime information including memory usage
      and execution time. Provides verbose output for troubleshooting.

<info>Examples:</info>
  # Show system status and configuration
  vendor/bin/typo3 sudhaus7:wizard status

  # List all pending and completed wizard tasks
  vendor/bin/typo3 sudhaus7:wizard list

  # Process the next pending task with debug output
  vendor/bin/typo3 sudhaus7:wizard next --debug

  # Execute a specific task by ID with database logging
  vendor/bin/typo3 sudhaus7:wizard single --id=42 --logtodatabase

  # Get detailed information about a specific task
  vendor/bin/typo3 sudhaus7:wizard info --id=42

  # Force re-execution of a task and save processing map
  vendor/bin/typo3 sudhaus7:wizard single --id=42 --force --mapto=/tmp/wizard-logs
HELP
        );
        $this->addArgument('mode', InputArgument::REQUIRED, 'The operation mode: status, list, info, next, or single');
        $this->addOption('id', 'i', InputOption::VALUE_REQUIRED, 'UID of a specific wizard task (required for "single" mode, optional for "info" mode)');
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'Force execution of the task, bypassing status checks and allowing reprocessing');
        $this->addOption('mapto', 'm', InputOption::VALUE_REQUIRED, 'Directory path where the processing map/log should be written');
        $this->addOption('logtodatabase', null, InputOption::VALUE_NONE, 'Store detailed execution logs in the database (linked to creator record)');
        $this->addOption('debug', null, InputOption::VALUE_NONE, 'Enable debug mode with verbose output including runtime and memory usage statistics');
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        if ($input->getOption('debug')) {
            $this->logger = new DebugConsoleLogger($output);
        } else {
            $this->logger = new ConsoleLogger($output);
        }
        $this->repository = GeneralUtility::makeInstance(CreatorRepository::class);
    }

    /**
     * @throws Exception
     * @throws DBALException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $mapFolder = null;
        if ($input->getOption('mapto')) {
            $mapFolder = $input->getOption('mapto');
        }

        switch ($input->getArgument('mode')) {
            case 'info':
                if ($input->getOption('id')) {
                    $force = false;
                    if ($input->getOption('force')) {
                        $force = true;
                    }
                    $o = $this->repository->findByIdentifier($input->getOption('id'), $force);
                    if ($o instanceof Creator) {
                        $this->getInfo($o, $input, $output);
                        return Command::SUCCESS;
                    }
                    $output->writeln('<info>Not found</info>');
                } else {
                    $o = $this->repository->findNext();
                    if ($o instanceof Creator) {
                        $this->getInfo($o, $input, $output);
                        return Command::SUCCESS;
                    }
                }
                break;
            case 'single':
                if ($input->getOption('id')) {
                    $force = false;
                    if ($input->getOption('force')) {
                        $force = true;
                    }
                    $o = $this->repository->findByIdentifier($input->getOption('id'), $force);
                    if ($o instanceof Creator) {
                        return $this->create($o, $input, $output, $mapFolder);
                    }
                }
                return 0;
            case 'status':
                $this->getStatus($input, $output);
                return 0;
            case 'list':
                $this->getList($input, $output);
                return 0;
            case 'next':
                $o = $this->repository->findNext();
                if ($o instanceof Creator) {
                    if (!$this->repository->isRunning()) {
                        return $this->create($o, $input, $output, $mapFolder);
                    }
                } else {
                    $output->writeln('<info>🎆 No wizard jobs to process 🎆</info>');
                    return Command::SUCCESS;
                }
                break;
            default:
                break;
        }
        return 1;
    }

    /**
     * @deprecated
     */
    private function forceVisible(int $id): void
    {
        GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('tx_sudhaus7wizard_domain_model_creator')
            ->update(
                'tx_sudhaus7wizard_domain_model_creator',
                ['uid' => $id],
                ['hidden' => 0, 'deleted' => 0, 'status' => 10]
            );
    }

    public function getInfo(Creator $o, InputInterface $input, OutputInterface $output): void
    {
        $output->write(sprintf("Generiere Baukasten %s\n", $o->getLongname()));
        $output->write("Vorlage:\t" . $o->getBase() . "\n");
        $output->write("Projektname:\t" . $o->getProjektname() . "\n");
        $output->write("Kurzname:\t" . $o->getShortname() . "\n");
        $output->write("Domain:\t" . $o->getDomainname() . "\n");
        $output->write("Kontakt:\t" . $o->getContact() . "\n");
        $output->write("Benutzer:\t" . $o->getReduser() . ' ' . $o->getRedemail() . "\n");

        $a = $o->getFlexinfo();
        foreach ($a['data']['sDEF']['lDEF'] as $k => $v) {
            $output->write(sprintf("%s:\t%s\n", ucfirst((string)$k), $v['vDEF']));
        }
    }

    public function create(Creator $creator, InputInterface $input, OutputInterface $output, $mapfolder = null): int
    {
        if ($input->getOption('logtodatabase')) {
            // Start a new log
            GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable(WizardDatabaseLogger::TABLE)->delete(
                WizardDatabaseLogger::TABLE,
                ['creator' => $creator->getUid()]
            );
            $this->logger = new WizardDatabaseLogger($creator, $this->logger);
        }

        Bootstrap::initializeBackendAuthentication();
        $creator->setStatus(Creator::STATUS_PROCESSING);
        $this->repository->updateStatus($creator);

        $this->getInfo($creator, $input, $output);
        //$output->write(implode("\n",)."\n");

        try {
            if ($this->createProcessFactory->get($creator, $this->logger)->run($mapfolder)) {
                $output->write("Fertig\n", true);
                $creator->setStatus(Creator::STATUS_DONE);

                $this->repository->updateStatus($creator);
                $this->repository->updatePid($creator);

                if (!empty($creator->getNotifyEmail())) {
                    // Create the message
                    /** @var MailMessage $mail */
                    $mail = GeneralUtility::makeInstance(MailMessage::class);

                    // Prepare and send the message
                    $mail->setSubject(sprintf('[Wizard] %s ist fertig', $creator->getProjektname()))
                        //->setFrom($creator->getNotifyEmail())
                        ->setTo($creator->getNotifyEmail())
                        ->text(sprintf('Der neue Baukasten %s wurde angelegt', $creator->getProjektname()));
                    $mail->send();
                    $output->write("E-Mail versendet\n");
                }

                return Command::SUCCESS;
            }
        } catch (Throwable $e) {
            $this->logger->warning($e->getMessage() . ' (' . $e->getCode() . ")\n" . $e->getTraceAsString(), []);
            $creator->setStacktrace($e->getMessage() . ' (' . $e->getCode() . ")\n" . $e->getTraceAsString());
            $creator->setStatus(Creator::STATUS_FAILED);
        }

        $this->repository->updateStatus($creator);

        return Command::FAILURE;
    }

    public function getStatus(InputInterface $input, OutputInterface $output): void
    {
        //$this->mylist($input, $output);
        $output->writeln([
            '-------------------------------------',
            print_r(Tools::getRegisteredExtensions(), true),
            print_r(Tools::getCreatorConfig(), true),

        ], $output::VERBOSITY_NORMAL);
    }

    public function getList(InputInterface $input, OutputInterface $output): void
    {
        $table = new Table($output);
        $table->setHeaderTitle('Todo List');
        $table->setHeaders(['ID', 'Baukasten', 'Status']);

        $list = $this->repository->findAll();
        /** @var $o Creator */
        foreach ($list as $o) {
            $table->addRow([$o->getUid(), $o->getLongname(), $o->getStatusLabel()]);
        }
        $table->render();
    }
}
