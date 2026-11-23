<?php

declare(strict_types=1);

namespace SUDHAUS7\Sudhaus7Wizard\Tests\Functional\Localization;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use SBUERK\TYPO3\Testing\TestCase\FunctionalTestCase;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;

/**
 * Resolves the backend labels of the creator form through the core's language service, as FormEngine does.
 */
final class LabelResolutionTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'sudhaus7/sudhaus7-wizard',
    ];

    #[Test]
    #[DataProvider('labelDataProvider')]
    public function labelIsResolvedForTheBackendLanguage(string $language, string $key, string $expected): void
    {
        $languageService = $this->get(LanguageServiceFactory::class)->create($language);

        $label = $languageService->sL('LLL:EXT:sudhaus7_wizard/Resources/Private/Language/locallang.xlf:' . $key);

        self::assertSame($expected, $label);
    }

    public static function labelDataProvider(): \Generator
    {
        yield 'English table title' => [
            'language' => 'default',
            'key' => 'tx_sudhaus7wizard_domain_model_creator',
            'expected' => 'Site Wizard Task',
        ];
        yield 'English field label' => [
            'language' => 'default',
            'key' => 'tx_sudhaus7wizard_domain_model_creator.contact',
            'expected' => 'Contact E-Mail (e.q. info@our-little-farm.com)',
        ];
        yield 'German table title' => [
            'language' => 'de',
            'key' => 'tx_sudhaus7wizard_domain_model_creator',
            'expected' => 'Site Wizard Auftrag',
        ];
        yield 'German contact label' => [
            'language' => 'de',
            'key' => 'tx_sudhaus7wizard_domain_model_creator.contact',
            'expected' => 'Kontakt E-Mail (z.B. info@unsere-kleine-farm.de)',
        ];
        yield 'German editor username label' => [
            'language' => 'de',
            'key' => 'tx_sudhaus7wizard_domain_model_creator.reduser',
            'expected' => 'Redakteur Benutzername (z.B. unserekleinefarm - wird beim Anmelden verwendet)',
        ];
        yield 'German theme configuration label' => [
            'language' => 'de',
            'key' => 'tx_sudhaus7wizard_domain_model_creator.flexinfo',
            'expected' => 'Weitere Konfiguration',
        ];
        yield 'German label without a source in the translation' => [
            'language' => 'de',
            'key' => 'tx_sudhaus7wizard_domain_model_creator.base',
            'expected' => 'Template/Vorlage',
        ];
    }
}
