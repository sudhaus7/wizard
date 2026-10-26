<?php

declare(strict_types=1);

namespace SUDHAUS7\Sudhaus7Wizard\Tests\Functional\Wizard;

use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use SBUERK\TYPO3\Testing\TestCase\FunctionalTestCase;
use SUDHAUS7\Sudhaus7Wizard\Cli\RunCommand;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;

/**
 * Runs the wizard on the template tree of Fixtures/Database/content.csv and checks the cloned pages and
 * content. Clones are looked up by slug and header, so the assertions do not depend on generated uids.
 */
final class PageAndContentGenerationTest extends FunctionalTestCase
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
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Database/content.csv');

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

    #[Test]
    public function pagesBelowTheSourcePageAreClonedIntoTheNewSite(): void
    {
        $this->runWizard();

        $newRootUid = $this->findClonedRecord('pages', 'slug', '/', 1)['uid'];
        $linksPage = $this->findClonedRecord('pages', 'slug', '/links', 30);
        $childPage = $this->findClonedRecord('pages', 'slug', '/links/child', 34);

        self::assertSame($newRootUid, $linksPage['pid']);
        self::assertSame($linksPage['uid'], $childPage['pid']);
        self::assertSame('Links', $linksPage['title']);
        self::assertSame(1024, $linksPage['sorting']);
    }

    #[Test]
    public function hiddenPagesAreClonedHidden(): void
    {
        $this->runWizard();

        $hiddenPage = $this->findClonedRecord('pages', 'slug', '/hidden-page', 32);

        self::assertSame(1, $hiddenPage['hidden']);
    }

    #[Test]
    public function deletedPagesAndPagesOutsideTheSourceTreeAreNotCloned(): void
    {
        $this->runWizard();

        self::assertSame(1, $this->countRecords('pages', 'slug', '/deleted-page'));
        self::assertSame(1, $this->countRecords('pages', 'slug', '/other-site'));
    }

    #[Test]
    public function shortcutPagePointsToTheClonedTargetPage(): void
    {
        $this->runWizard();

        $linksPage = $this->findClonedRecord('pages', 'slug', '/links', 30);
        $shortcutPage = $this->findClonedRecord('pages', 'slug', '/shortcut-to-links', 31);

        self::assertSame((string)$linksPage['uid'], (string)$shortcutPage['shortcut']);
    }

    #[Test]
    public function contentElementsAreClonedToTheClonedPage(): void
    {
        $this->runWizard();

        $linksPage = $this->findClonedRecord('pages', 'slug', '/links', 30);
        $textElement = $this->findClonedRecord('tt_content', 'header', 'Text with links', 100);
        $hiddenElement = $this->findClonedRecord('tt_content', 'header', 'Hidden element', 101);

        self::assertSame($linksPage['uid'], $textElement['pid']);
        self::assertSame('text', $textElement['CType']);
        self::assertSame(256, $textElement['sorting']);
        self::assertSame($linksPage['uid'], $hiddenElement['pid']);
        self::assertSame(1, $hiddenElement['hidden']);
        self::assertSame(1, $hiddenElement['colPos']);
    }

    #[Test]
    public function deletedContentElementsAreNotCloned(): void
    {
        $this->runWizard();

        self::assertSame(1, $this->countRecords('tt_content', 'header', 'Deleted element'));
    }

    #[Test]
    public function pageLinkInLinkFieldPointsToTheClonedPage(): void
    {
        $this->runWizard();

        $childPage = $this->findClonedRecord('pages', 'slug', '/links/child', 34);
        $textElement = $this->findClonedRecord('tt_content', 'header', 'Text with links', 100);

        self::assertSame('t3://page?uid=' . $childPage['uid'], $textElement['header_link']);
    }

    #[Test]
    public function pageLinksInRichTextPointToTheClonedPageOrKeepPagesOutsideTheSourceTree(): void
    {
        $this->runWizard();

        $childPage = $this->findClonedRecord('pages', 'slug', '/links/child', 34);
        $textElement = $this->findClonedRecord('tt_content', 'header', 'Text with links', 100);

        self::assertSame(
            '<p><a href="t3://page?uid=' . $childPage['uid'] . '">Child</a> <a href="t3://page?uid=40">Other site</a></p>',
            $textElement['bodytext']
        );
    }

    #[Test]
    public function shortcutContentElementReferencesTheClonedContentElement(): void
    {
        $this->runWizard();

        $textElement = $this->findClonedRecord('tt_content', 'header', 'Text with links', 100);
        $reference = $this->findClonedRecord('tt_content', 'header', 'Reference to text', 103);

        self::assertSame('tt_content_' . $textElement['uid'], $reference['records']);
    }

    #[Test]
    public function translatedContentElementPointsToTheClonedDefaultLanguageElement(): void
    {
        $this->runWizard();

        $textElement = $this->findClonedRecord('tt_content', 'header', 'Text with links', 100);
        $translation = $this->findClonedRecord('tt_content', 'header', 'Translated text', 104);

        self::assertSame(1, $translation['sys_language_uid']);
        self::assertSame($textElement['uid'], $translation['l18n_parent']);
    }

    private function runWizard(): void
    {
        $tester = new CommandTester($this->get(RunCommand::class));
        $tester->execute([
            'mode' => 'next',
        ]);
        $tester->assertCommandIsSuccessful($tester->getDisplay());
    }

    /**
     * Returns the clone of a template record, identified by a field value it shares with the template.
     *
     * @return array<string, mixed>
     */
    private function findClonedRecord(string $table, string $field, string $value, int $templateUid): array
    {
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();
        $rows = $queryBuilder
            ->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq($field, $queryBuilder->createNamedParameter($value)),
                $queryBuilder->expr()->neq('uid', $queryBuilder->createNamedParameter($templateUid, Connection::PARAM_INT)),
            )
            ->executeQuery()
            ->fetchAllAssociative();

        self::assertCount(1, $rows, sprintf('Expected exactly one clone of %s:%d', $table, $templateUid));
        $row = $rows[0];
        foreach (['uid', 'pid', 'hidden', 'sorting', 'colPos', 'sys_language_uid', 'l18n_parent'] as $integerField) {
            if (isset($row[$integerField])) {
                $row[$integerField] = (int)$row[$integerField];
            }
        }
        return $row;
    }

    private function countRecords(string $table, string $field, string $value): int
    {
        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()->removeAll();
        return (int)$queryBuilder
            ->count('uid')
            ->from($table)
            ->where($queryBuilder->expr()->eq($field, $queryBuilder->createNamedParameter($value)))
            ->executeQuery()
            ->fetchOne();
    }
}
