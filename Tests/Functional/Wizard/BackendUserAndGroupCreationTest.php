<?php

declare(strict_types=1);

namespace SUDHAUS7\Sudhaus7Wizard\Tests\Functional\Wizard;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use SBUERK\TYPO3\Testing\TestCase\FunctionalTestCase;
use SUDHAUS7\Sudhaus7Wizard\Cli\RunCommand;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;

/**
 * Runs the wizard with backend user and group records whose relation fields hold NULL, as core stores them in
 * nullable columns.
 */
final class BackendUserAndGroupCreationTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => [
            'id' => 0,
            'title' => 'English',
            'locale' => 'en_US.UTF8',
        ],
    ];

    protected array $testExtensionsToLoad = [
        'sudhaus7/sudhaus7-wizard',
        'sudhaus7/template',
    ];

    protected array $pathsToProvideInTestInstance = [
        'typo3conf/ext/sudhaus7_wizard/Tests/Functional/Wizard/Fixtures/Fileadmin/' => 'fileadmin/',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Database/template.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Database/be_groups.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Database/be_users.csv');

        $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($GLOBALS['BE_USER']);

        $this->writeSiteConfiguration(
            identifier: 'acme',
            site: $this->buildSiteConfiguration(
                rootPageId: 1,
                base: 'https://localhost/',
                websiteTitle: 'ACME',
            ),
            languages: [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ],
        );
    }

    /**
     * @param array<string, mixed> $values
     */
    #[Test]
    #[DataProvider('emptyRelationDataProvider')]
    public function editorIsCreatedWithTheNewGroupWhenRelationFieldsAreNull(string $table, array $values, ?int $uid): void
    {
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable($table);
        if ($uid === null) {
            $connection->insert($table, $values);
        } else {
            $connection->update($table, $values, ['uid' => $uid]);
        }

        $tester = new CommandTester($this->get(RunCommand::class));
        $tester->execute([
            'mode' => 'next',
        ]);

        $tester->assertCommandIsSuccessful($tester->getDisplay());
        $editor = $this->get(ConnectionPool::class)->getConnectionForTable('be_users')
            ->select(['usergroup'], 'be_users', ['username' => 'ready'])
            ->fetchAssociative();
        self::assertIsArray($editor);
        self::assertNotEmpty($editor['usergroup']);
    }

    public static function emptyRelationDataProvider(): \Generator
    {
        yield 'template group without file mounts' => [
            'table' => 'be_groups',
            'values' => [
                'file_mountpoints' => null,
            ],
            'uid' => 22,
        ];
        yield 'template user without groups' => [
            'table' => 'be_users',
            'values' => [
                'usergroup' => null,
            ],
            'uid' => 10,
        ];
        yield 'existing editor without groups and file mounts' => [
            'table' => 'be_users',
            'values' => [
                'pid' => 0,
                'username' => 'ready',
                'usergroup' => null,
                'file_mountpoints' => null,
            ],
            'uid' => null,
        ];
    }
}
