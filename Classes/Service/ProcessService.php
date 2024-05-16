<?php

declare(strict_types=1);

namespace SUDHAUS7\Sudhaus7Wizard\Service;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Driver\Exception;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use SUDHAUS7\Sudhaus7Wizard\Domain\Dto\CreateProcessInterface;
use SUDHAUS7\Sudhaus7Wizard\Domain\Dto\PrepareProcess;
use SUDHAUS7\Sudhaus7Wizard\Domain\Model\Creator;
use SUDHAUS7\Sudhaus7Wizard\Domain\Repository\CreatorRepository;
use SUDHAUS7\Sudhaus7Wizard\Enumeration\CreatorStatus;
use SUDHAUS7\Sudhaus7Wizard\Events\AfterCreateFilemountEvent;
use SUDHAUS7\Sudhaus7Wizard\Events\CreateFilemountEvent;
use SUDHAUS7\Sudhaus7Wizard\Exception\DataHandlerExecutionFailedException;
use SUDHAUS7\Sudhaus7Wizard\Services\CreateProcessFactory;
use SUDHAUS7\Sudhaus7Wizard\Tools;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Mail\MailMessage;
use TYPO3\CMS\Core\Resource\Exception\ExistingTargetFolderException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderWritePermissionsException;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class ProcessService
{
    public function __construct(
        private CreatorRepository $creatorRepository,
        private EventDispatcherInterface $eventDispatcher,
        private \SUDHAUS7\Sudhaus7Wizard\CreateProcess\CreateProcessFactory $createProcessFactory,
        private DataHandlingService $dataHandlingService,
        private LoggerInterface $logger
    ) {}
    public function create(PrepareProcess $prepareProcess, ?OutputInterface $output = null): int
    {
        $prepareProcess->getCreator()->setStatus(CreatorStatus::STATUS_PROCESSING);
        $this->creatorRepository->updateStatus($prepareProcess->getCreator());
        $this->printInformation($prepareProcess->getCreator(), $output);
        $createProcess = $this->createProcessFactory;
        try {
            if (CreateProcessFactory::get($prepareProcess->getCreator(), $prepareProcess->getLogger())
                ->run($prepareProcess->getMappingFolder())) {
                $output->write("Fertig\n", true);
                $prepareProcess->getCreator()->setStatus(20);

                $this->creatorRepository->updateStatus($prepareProcess->getCreator());
                $this->creatorRepository->updatePid($prepareProcess->getCreator());

                $user = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getConnectionForTable('be_users')
                    ->select(
                        ['*'],
                        'be_users',
                        ['uid' => $prepareProcess->getCreator()->getCruserId()],
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
                    $mail->setSubject(sprintf('[Wizard] %s ist fertig', $prepareProcess->getCreator()->getProjektname()))
                        ->setFrom($user['email'])
                        ->setTo($user['email'])
                        ->text(sprintf('Der neue Baukasten %s wurde angelegt', $prepareProcess->getCreator()->getProjektname()));
                    $mail->send();
                    $output->write("E-Mail versendet\n");
                }

                return Command::SUCCESS;
            }
        } catch (Exception|ExtensionConfigurationExtensionNotConfiguredException|ExtensionConfigurationPathDoesNotExistException|\Exception $e) {
            $prepareProcess->getLogger()->warning($e->getMessage(), $e->getTrace());
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
            ['Template', $creator->getWizardProcessClass()],
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

    private function run(CreateProcessInterface $createProcess): int
    {
        $this->createFileMount($createProcess);
    }

    /**
     * @throws InsufficientFolderAccessPermissionsException
     * @throws DataHandlerExecutionFailedException
     * @throws Exception
     * @throws InsufficientFolderWritePermissionsException
     */
    private function createFileMount(CreateProcessInterface $createProcess): void
    {
        $shortname = Tools::generateSlug($createProcess->getCreator()->getShortname());
        $dir = $createProcess->getWizardProcess()->getMediaBaseDir() . $shortname . '/';
        $name = 'Medien ' . $createProcess->getCreator()->getProjektname();

        $event = new CreateFilemountEvent([
            'title' => $name,
            'path' => $dir,
            'base' => 1,
            'pid' => 0,
        ], $this);
        $this->eventDispatcher->dispatch($event);
        $fileMountRecord = $event->getRecord();

        $this->logger->debug(sprintf('Create Filemount 1 %s - %s', $fileMountRecord['title'], $fileMountRecord['path']));

        $result = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_filemounts')
            ->select(
                ['*'],
                'sys_filemounts',
                [
                    'path' => $fileMountRecord['path'],
                ]
            );

        $existingFileMount = $result->fetchAssociative();
        if ($existingFileMount !== false) {
            $createProcess->setFileMount($existingFileMount);
            $event = new AfterCreateFilemountEvent($existingFileMount, $this);
            $this->eventDispatcher->dispatch($event);
            return;
        }

        $storage = GeneralUtility::makeInstance(ResourceFactory::class)
            ->getDefaultStorage();
        try {
            $storage->createFolder($fileMountRecord['path']);
        }
            // If the folder exists, continue creating record.
            // All other exceptions should be thrown
        catch (ExistingTargetFolderException $_) {}

        $newFileMountId = $this->dataHandlingService->immediatelyAddRecord('sys_filemounts', $fileMountRecord);

        $fileMount = BackendUtility::getRecord(
            'sys_filemounts',
            $newFileMountId
        );
        $createProcess->setFileMount($fileMount);
        $event = new AfterCreateFilemountEvent($fileMount, $this);
        $this->eventDispatcher->dispatch($event);
    }
}
