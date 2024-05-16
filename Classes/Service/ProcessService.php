<?php

declare(strict_types=1);

namespace SUDHAUS7\Sudhaus7Wizard\Service;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use SUDHAUS7\Sudhaus7Wizard\Domain\Dto\Process;
use SUDHAUS7\Sudhaus7Wizard\Domain\Model\Creator;
use SUDHAUS7\Sudhaus7Wizard\Domain\Repository\CreatorRepository;
use SUDHAUS7\Sudhaus7Wizard\Enumeration\CreatorStatus;
use SUDHAUS7\Sudhaus7Wizard\Services\CreateProcessFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class ProcessService
{
    public function __construct(
        private CreatorRepository $creatorRepository
    ) {}
    public function create(Process $process, ?OutputInterface $output = null): int
    {
        $process->getCreator()->setStatus(CreatorStatus::STATUS_PROCESSING);
        $this->creatorRepository->updateStatus($process->getCreator());
        $this->printInformation($process->getCreator(), $output);
        try {
            if (CreateProcessFactory::get($process->getCreator(), $process->getLogger())->run($process->getMappingFolder())) {
                $output->write("Fertig\n", true);
                $process->getCreator()->setStatus(20);

                $this->creatorRepository->updateStatus($process->getCreator());
                $this->creatorRepository->updatePid($process->getCreator());

                $user = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getConnectionForTable('be_users')
                    ->select(
                        ['*'],
                        'be_users',
                        ['uid' => $process->getCreator()->getCruserId()],
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
                    $mail->setSubject(sprintf('[Wizard] %s ist fertig', $process->getCreator()->getProjektname()))
                        ->setFrom($user['email'])
                        ->setTo($user['email'])
                        ->text(sprintf('Der neue Baukasten %s wurde angelegt', $process->getCreator()->getProjektname()));
                    $mail->send();
                    $output->write("E-Mail versendet\n");
                }

                return Command::SUCCESS;
            }
        } catch (Exception|ExtensionConfigurationExtensionNotConfiguredException|ExtensionConfigurationPathDoesNotExistException|\Exception $e) {
            $process->getLogger()->warning($e->getMessage(), $e->getTrace());
        }

        return Command::FAILURE;
    }

    public function printInformation(Creator $creator, ?OutputInterface $output): void
    {
        if (!($output instanceof OutputInterface)) {
            return;
        }
        $outputTable = new Table($output);
        $outputTable->setHeaderTitle(sprintf('<info>Generate new TYPO3 page "%s"</info>', $creator->getLongname()));

        $informationArray = [
            ['Template', $creator->getBase()],
            ['Project name', $creator->getProjektname()],
            ['Short name', $creator->getShortname()],
            ['Domain', $creator->getDomainname()],
            ['Contact', $creator->getContact()],
            ['User', sprintf('%s (%s)', $creator->getReduser(), $creator->getRedemail())],
        ];

        $flexFormInformation = $creator->getFlexinfo();
        foreach ($flexFormInformation['data']['sDEF']['lDEF'] ?? [] as $key => $value) {
            $informationArray[] = [ucfirst((string)$key), $value['vDEF'] ?? ''];
        }

        $outputTable->addRows($informationArray);
        $outputTable->render();
    }

    /**
     * @throws Exception
     * @throws DBALException
     */
    public function printList(OutputInterface $output): void
    {
        $table = new Table($output);
        $table->setHeaderTitle('Todo List');
        $table->setHeaders(['ID', 'TYPO3 Page', 'State']);

        $list = $this->creatorRepository->findAll();
        foreach ($list as $creator) {
            $table->addRow([$creator->getUid(), $creator->getLongname(), $creator->getStatusLabel()]);
        }
        $table->render();
    }
}
