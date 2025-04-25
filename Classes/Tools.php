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

namespace SUDHAUS7\Sudhaus7Wizard;

use SUDHAUS7\Sudhaus7Wizard\Interfaces\WizardProcessInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class Tools
{
    /**
     * @return array<array-key, mixed>|null
     */
    public static function getRegisteredExtensions(): ?array
    {
        return $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['Sudhaus7Wizard']['registeredTemplateExtentions'];
    }

    /**
     * @return array{flex: mixed, types: mixed}
     */
    public static function getCreatorConfig(): array
    {
        return [
            'flex' => $GLOBALS['TCA']['tx_sudhaus7wizard_domain_model_creator']['columns']['flexinfo']['config']['ds'],
            'types' => $GLOBALS['TCA']['tx_sudhaus7wizard_domain_model_creator']['types'],
        ];
    }

    /**
     * @param class-string $class
     */
    public static function registerWizardProcess(string $class): void
    {
        if (!in_array(WizardProcessInterface::class, class_implements($class))) {
            return;
        }

        /** @var WizardProcessInterface $class */
        $config = $class::getWizardConfig();

        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['Sudhaus7Wizard']['registeredTemplateExtentions'][$config->getExtension()] = $class;
        if (is_array($GLOBALS['TCA']['tx_sudhaus7wizard_domain_model_creator']) && !isset($GLOBALS['TCA']['tx_sudhaus7wizard_domain_model_creator']['columns']['flexinfo']['config']['ds'][$config->getExtension()])) {
            $GLOBALS['TCA']['tx_sudhaus7wizard_domain_model_creator']['columns']['base']['config']['items'][] = [
                $config->getDescription(),
                $config->getExtension(),
            ];
            $GLOBALS['TCA']['tx_sudhaus7wizard_domain_model_creator']['columns']['flexinfo']['config']['ds'][$config->getExtension()] = $config->getFlexinfoFile();
            $GLOBALS['TCA']['tx_sudhaus7wizard_domain_model_creator']['types'][$config->getExtension()] = [
                'showitem' => '
                status,base,sourceclass,sourcepid,projektname,longname,shortname,domainname,contact,email,--div--;Benutzer,reduser,redpass,redemail,--div--;Template Konfigurationen,flexinfo,--div--;Log,log,stacktrace
            ' . $config->getAddFields(),
            ];
            $GLOBALS['TCA']['tx_sudhaus7wizard_domain_model_creator']['columns']['sourcepid']['config']['default'] = $config->getSourcePid();

            $GLOBALS['TCA']['tx_sudhaus7wizard_domain_model_creator'] = $config->modifyRecordTCA($GLOBALS['TCA']['tx_sudhaus7wizard_domain_model_creator']);
        }
    }

    public static function generateSlug(string $str): ?string
    {
        $str = mb_strtolower(trim($str));
        $str = str_replace(
            [
                'ß',
                'ä',
                'ü',
                'ö',
            ],
            [
                'ss',
                'ae',
                'ue',
                'oe',
            ],
            $str
        );
        // Trim incl. dashes
        $str = trim($str, '-');
        if (function_exists('iconv')) {
            $str = iconv('utf-8', 'us-ascii//TRANSLIT', $str);
        }
        $str = preg_replace('/[^a-z0-9-]/', '-', (string)$str);

        return preg_replace('/-+/', '-', (string)$str);
    }

    /**
     * @param array<array-key, mixed> $a
     */
    public static function array2xml(array $a): string
    {
        $flexObj = GeneralUtility::makeInstance(FlexFormTools::class);
        return $flexObj->flexArray2Xml($a, true);
    }

    /**
     * @param array<array-key, mixed> $record
     * @return array<array-key, mixed>
     */
    public static function resolveFieldConfigurationAndRespectColumnsOverrides(
        string $table,
        string $field,
        array $record
    ): array {
        $tcaFieldConf = $GLOBALS['TCA'][$table]['columns'][$field]['config'];
        $recordType = BackendUtility::getTCAtypeValue($table, $record);
        $columnsOverridesConfigOfField = $GLOBALS['TCA'][$table]['types'][$recordType]['columnsOverrides'][$field]['config'] ?? null;
        if ($columnsOverridesConfigOfField) {
            ArrayUtility::mergeRecursiveWithOverrule($tcaFieldConf, $columnsOverridesConfigOfField);
        }
        return $tcaFieldConf;
    }
}
