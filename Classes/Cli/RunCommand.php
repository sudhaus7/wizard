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
use SUDHAUS7\Sudhaus7Wizard\Domain\Model\Creator;
use SUDHAUS7\Sudhaus7Wizard\Domain\Repository\CreatorRepository;
use SUDHAUS7\Sudhaus7Wizard\Services\CreateProcessFactoryInterface;
use SUDHAUS7\Sudhaus7Wizard\Tools;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class RunCommand extends Command
{
    public ?ConsoleLogger $logger = null;
    private ?CreatorRepository $repository = null;
    private CreateProcessFactoryInterface $createProcessFactory;

    public function __construct(CreateProcessFactoryInterface $createProcessFactory)
    {
        parent::__construct();
        $this->createProcessFactory = $createProcessFactory;
    }

    protected function configure(): void
    {
        $this->setDescription('Baukasten Wizard');
        $this->setHelp('vendor/bin/typo3 sudhaus7:wizard status');
        $this->addArgument('mode', InputArgument::REQUIRED, 'The mode, either status, list, next or single');
        $this->addOption('id', 'i', InputOption::VALUE_REQUIRED, 'in mode single, the uid of a specific task');
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'force running the task');
        $this->addOption('mapto', 'm', InputOption::VALUE_REQUIRED, 'write the map to this folder');
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->logger = new ConsoleLogger($output);
        $this->repository = GeneralUtility::makeInstance(CreatorRepository::class);
    }

    /**
     * @throws \Doctrine\DBAL\Driver\Exception
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
        Bootstrap::initializeBackendAuthentication();
        $creator->setStatus(15);
        $this->repository->updateStatus($creator);
        $this->getInfo($creator, $input, $output);
        //$output->write(implode("\n",)."\n");

        try {
            if ($this->createProcessFactory->get($creator, $this->logger)->run($mapfolder)) {
                $output->write("Fertig\n", true);
                $creator->setStatus(20);

                $this->repository->updateStatus($creator);
                $this->repository->updatePid($creator);

                $user = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getConnectionForTable('be_users')
                    ->select(
                        ['*'],
                        'be_users',
                        ['uid' => $creator->getCruserId()],
                        [],
                        [],
                        1
                    )
                    ->fetchAssociative();

                if (!empty($user['email'])) {
                    // Create the message
                    /** @var MailMessage $mail */
                    $mail = GeneralUtility::makeInstance(MailMessage::class);

                    // Prepare and send the message
                    $mail->setSubject(sprintf('[Wizard] %s ist fertig', $creator->getProjektname()))
                        ->setFrom($user['email'])
                        ->setTo($user['email'])
                        ->text(sprintf('Der neue Baukasten %s wurde angelegt', $creator->getProjektname()));
                    $mail->send();
                    $output->write("E-Mail versendet\n");
                }

                return Command::SUCCESS;
            }
        } catch (Exception|ExtensionConfigurationExtensionNotConfiguredException|ExtensionConfigurationPathDoesNotExistException $e) {
            $this->logger->warning($e->getMessage(), $e->getTrace());
        }

        $creator->setStatus(17);
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
