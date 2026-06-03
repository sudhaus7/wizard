<?php

declare(strict_types=1);

namespace SUDHAUS7\Sudhaus7Wizard\Tests\Functional\Wizard;

use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\SiteHandling\SiteBasedTestTrait;
use SBUERK\TYPO3\Testing\TestCase\FunctionalTestCase;
use SUDHAUS7\Sudhaus7Wizard\Cli\RunCommand;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;

final class WizardTest extends FunctionalTestCase
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
        /** @var LanguageServiceFactory $languageServiceFactory */
        $languageServiceFactory = $this->get(LanguageServiceFactory::class);
        $GLOBALS['LANG'] = $languageServiceFactory->createFromUserPreferences($GLOBALS['BE_USER']);

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
            additional: [
                'dependencies' => [
                    'my-vendor/site-set-identifier',
                ],
            ],
        );

        $this->setUpFrontendRootPage(
            pageId: 1,
            createSysTemplateRecord: false,
        );
    }

    #[Test]
    public function wizardListReturnsFullList(): void
    {
        $tester = new CommandTester($this->get(RunCommand::class));
        $tester->execute([
            'mode' => 'list',
        ]);
        $tester->assertCommandIsSuccessful();
        $output = $tester->getDisplay();
        self::assertEquals('+----+-------- Todo List -+-----------+
| ID | Baukasten          | Status    |
+----+--------------------+-----------+
| 1  | Ready Template     | ready     |
| 2  | Not Ready Template | Not ready |
+----+--------------------+-----------+
', $output);
    }

    /**
     * @todo postgres is currently disabled due to a mismatch of the generated record uids.
     *       This has to be investigated, why this is happening.
     */
    #[Test]
    #[Group('not-postgres')]
    public function wizardGeneratesNewSite(): void
    {
        $tester = new CommandTester($this->get(RunCommand::class));
        $tester->execute([
            'mode' => 'next',
        ]);

        $tester->assertCommandIsSuccessful($tester->getDisplay());
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Results/siteGenerated.csv');

        /** @var SiteFinder $siteFinder */
        $siteFinder = $this->get(SiteFinder::class);
        $site = $siteFinder->getSiteByIdentifier('readytemplate');
        self::assertInstanceOf(Site::class, $site);
        self::assertEquals('wizard.dev', $site->getBase()->getHost());
        self::assertIsArray($site->getAttribute('dependencies'));
        self::assertEquals(['sudhaus7/template'], $site->getAttribute('dependencies'));
    }
}
