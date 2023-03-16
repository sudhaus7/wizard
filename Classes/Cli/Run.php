<?php

/*
 * This file is part of the TYPO3 project.
 * (c) 2022 B-Factor GmbH
 *          Sudhaus7
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 * The TYPO3 project - inspiring people to share!
 * @copyright 2022 B-Factor GmbH https://b-factor.de/
 * @author Frank Berger <fberger@b-factor.de>
 * @author Daniel Simon <dsimon@b-factor.de>
 */

namespace SUDHAUS7\Sudhaus7Wizard\Cli;

use SUDHAUS7\Sudhaus7Wizard\Create;
use SUDHAUS7\Sudhaus7Wizard\Domain\Model\Creator;
use SUDHAUS7\Sudhaus7Wizard\Domain\Repository\CreatorRepository;
use SUDHAUS7\Sudhaus7Wizard\Tools;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

class Run extends Command
{
    private ?ObjectManager $objectManager = null;
    private ?CreatorRepository $repository = null;
    private ?PersistenceManager $persistenceManager = null;

    public function mystatus(InputInterface $input, OutputInterface $output)
    {
        //$this->mylist($input, $output);
        $output->writeln([
            '-------------------------------------',
            print_r(Tools::getRegisteredExtentions(), true),
            print_r(Tools::getCreatorConfig(), true),

        ], $output::VERBOSITY_NORMAL);
    }

    public function mylist(InputInterface $input, OutputInterface $output)
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

    public function getInfo(Creator $o, InputInterface $input, OutputInterface $output)
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
            $output->write(sprintf("%s:\t%s\n", ucfirst($k), $v['vDEF']));
        }
    }

    public function create(Creator $o, InputInterface $input, OutputInterface $output, $mapfolder=null)
    {
        $o->setStatus(15);
        $this->persistenceManager->update($o);
        $this->persistenceManager->persistAll();
        $this->getInfo($o, $input, $output);
        //$output->write(implode("\n",)."\n");

        if (Create::taskFactory($o, $this, $output)->run($mapfolder)) {
            $output->write("Fertig\n", true);
            $o->setStatus(20);

            $this->persistenceManager->update($o);
            $this->persistenceManager->persistAll();

            $user  = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('be_users')->select(
                [ '*' ],
                'be_users',
                ['uid'=>$o->getCruserId()]
            )
            ->fetchAssociative();

            if (!empty($user['email'])) {
                // Create the message
                /** @var MailMessage $mail */
                $mail = GeneralUtility::makeInstance(MailMessage::class);

                // Prepare and send the message
                $mail->setSubject(sprintf('[Wizard] %s ist fertig', $o->getProjektname()))
                    ->setFrom($user['email'])
                    ->setTo($user['email'])
                    ->text(sprintf('Der neue Baukasten %s wurde angelegt', $o->getProjektname()));
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
     * @throws \TYPO3\CMS\Extbase\Object\Exception
     */
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->repository = $this->objectManager->get(CreatorRepository::class);
        $this->persistenceManager = $this->objectManager->get(PersistenceManager::class);
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

    private function forceVisible(int $id)
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
