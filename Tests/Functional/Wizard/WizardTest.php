<?php

declare(strict_types=1);

namespace SUDHAUS7\Sudhaus7Wizard\Tests\Functional\Wizard;

use PHPUnit\Framework\Attributes\Test;
use SUDHAUS7\Sudhaus7Wizard\Cli\RunCommand;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class WizardTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'sudhaus7/sudhaus7-wizard',
    ];

    protected array $pathsToProvideInTestInstance = [
        'typo3conf/ext/sudhaus7_wizard/Tests/Functional/Wizard/Fixtures/Sites/' => 'typo3conf/config/',
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

    #[Test]
    public function wizardGeneratesNewSite(): void
    {
        $tester = new CommandTester($this->get(RunCommand::class));
        $tester->execute([
            'mode' => 'next',
        ]);

        $tester->assertCommandIsSuccessful($tester->getDisplay());
        $this->assertCSVDataSet(__DIR__ . '/Fixtures/Results/siteGenerated.csv');
    }
}
