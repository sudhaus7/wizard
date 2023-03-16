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

use SUDHAUS7\Sudhaus7Wizard\Domain\Model\Creator;
use SUDHAUS7\Sudhaus7Wizard\Domain\Repository\CreatorRepository;
use SUDHAUS7\Sudhaus7Wizard\Services\CreateProcessFactory;
use SUDHAUS7\Sudhaus7Wizard\Tools;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\Exception;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

class Run extends Command
{
    private ?CreatorRepository $repository = null;
    private ?PersistenceManager $persistenceManager = null;

    public ?ConsoleLogger $logger = null;

    public function mystatus(InputInterface $input, OutputInterface $output): void
    {
        //$this->mylist($input, $output);
        $output->writeln([
            '-------------------------------------',
            print_r(Tools::getRegisteredExtentions(), true),
            print_r(Tools::getCreatorConfig(), true),

        ], $output::VERBOSITY_NORMAL);
    }

    public function mylist(InputInterface $input, OutputInterface $output): void
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

    public function create(Creator $creator, InputInterface $input, OutputInterface $output, $mapfolder=null): void
    {
        $creator->setStatus(15);
        $this->persistenceManager->update($creator);
        $this->persistenceManager->persistAll();
        $this->getInfo($creator, $input, $output);
        //$output->write(implode("\n",)."\n");

        if (CreateProcessFactory::get($creator, $this->logger)->run($mapfolder)) {
            $output->write("Fertig\n", true);
            $creator->setStatus(20);

            $this->persistenceManager->update($creator);
            $this->persistenceManager->persistAll();

            $user  = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('be_users')->select(
                [ '*' ],
                'be_users',
                ['uid'=>$creator->getCruserId()]
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
        }
    }

    protected function configure()
    {
        $this->setDescription('Baukasten Wizard');
        $this->setHelp('vendor/bin/typo3 sudhaus7:wizard status');
        $this->addArgument('mode', InputArgument::REQUIRED, 'The mode, either status, list, next or single');
        $this->addOption('id', 'i', InputOption::VALUE_REQUIRED, 'in mode single, the uid of a specific task');
        $this->addOption('force', 'f', InputOption::VALUE_NONE, 'force running the task');
        $this->addOption('mapto', 'm', InputOption::VALUE_REQUIRED, 'write the map to this folder');
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @throws Exception
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->logger = new ConsoleLogger($output);
        $this->repository = GeneralUtility::makeInstance(CreatorRepository::class);
        $this->persistenceManager = GeneralUtility::makeInstance(PersistenceManager::class);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $mapfolder = null;
        if ($input->getOption('mapto')) {
            $mapfolder = $input->getOption('mapto');
        }

        switch ($input->getArgument('mode')) {
            case 'info':
                if ($input->getOption('id')) {
                    if ($input->getOption('force')) {
                        $this->forceVisible($input->getOption('id'));
                    }
                    $o = $this->repository->findByIdentifier($input->getOption('id'));
                    if ($o) {
                        $this->getInfo($o, $input, $output);
                    }
                } else {
                    $o = $this->repository->findNext();
                    if ($o) {
                        $this->getInfo($o, $input, $output);
                    }
                }
                break;
            case 'single':
                if ($input->getOption('id')) {
                    if ($input->getOption('force')) {
                        $this->forceVisible($input->getOption('id'));
                    }
                    $o = $this->repository->findByIdentifier($input->getOption('id'));
                    if ($o) {
                        $this->create($o, $input, $output, $mapfolder);
                    }
                }
                return 0;
            case 'status':
                $this->mystatus($input, $output);
                return 0;
            case 'list':
                $this->mylist($input, $output);
                return 0;
            case 'next':
                $o = $this->repository->findNext();
                if ($o) {
                    $this->create($o, $input, $output, $mapfolder);
                }
                return 0;
            default:
                break;
        }
        return 1;
    }

    private function forceVisible(int $id): void
    {
        GeneralUtility::makeInstance(ConnectionPool::class)
                      ->getConnectionForTable('tx_sudhaus7wizard_domain_model_creator')
                      ->update(
                          'tx_sudhaus7wizard_domain_model_creator',
                          ['uid'=>$id],
                          ['hidden'=>0, 'deleted'=>0, 'status'=>10]
                      );
    }
}
