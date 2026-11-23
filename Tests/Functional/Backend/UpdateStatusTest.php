<?php

declare(strict_types=1);

namespace SUDHAUS7\Sudhaus7Wizard\Tests\Functional\Backend;

use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Saves creator records through the DataHandler, as the backend form does, and checks how the status hook
 * reacts to missing fields.
 */
final class UpdateStatusTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'sudhaus7/sudhaus7-wizard',
        'sudhaus7/template',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/creator.csv');
        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($GLOBALS['BE_USER']);
    }

    #[Test]
    public function statusReadyIsTakenOverWhenEveryCheckedFieldIsFilled(): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([
            'tx_sudhaus7wizard_domain_model_creator' => [
                2 => [
                    'status' => 10,
                ],
            ],
        ], []);
        $dataHandler->process_datamap();

        self::assertSame(10, $this->getStatus(2));
        self::assertSame([], $this->getWarnings());
    }

    #[Test]
    public function refusedStatusReadyIsReportedWithTheMissingFields(): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([
            'tx_sudhaus7wizard_domain_model_creator' => [
                1 => [
                    'status' => 10,
                ],
            ],
        ], []);
        $dataHandler->process_datamap();

        self::assertSame(0, $this->getStatus(1));
        $warnings = $this->getWarnings();
        self::assertCount(1, $warnings);
        self::assertStringContainsString('Contact E-Mail', $warnings[0]);
    }

    #[Test]
    public function savingADraftWithMissingFieldsIsNotReported(): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([
            'tx_sudhaus7wizard_domain_model_creator' => [
                1 => [
                    'projektname' => 'Still a draft',
                ],
            ],
        ], []);
        $dataHandler->process_datamap();

        self::assertSame(0, $this->getStatus(1));
        self::assertSame([], $this->getWarnings());
    }

    #[Test]
    public function contactIsRequiredInTheForm(): void
    {
        self::assertTrue(
            $GLOBALS['TCA']['tx_sudhaus7wizard_domain_model_creator']['columns']['contact']['config']['required'] ?? false
        );
    }

    private function getStatus(int $uid): int
    {
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('tx_sudhaus7wizard_domain_model_creator');
        $queryBuilder->getRestrictions()->removeAll();
        return (int)$queryBuilder
            ->select('status')
            ->from('tx_sudhaus7wizard_domain_model_creator')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchOne();
    }

    /**
     * @return list<string> the warnings queued for the editor
     */
    private function getWarnings(): array
    {
        $warnings = [];
        foreach ($this->get(FlashMessageService::class)->getMessageQueueByIdentifier()->getAllMessages() as $message) {
            if ($message->getSeverity() === ContextualFeedbackSeverity::WARNING) {
                $warnings[] = $message->getMessage();
            }
        }
        return $warnings;
    }
}
