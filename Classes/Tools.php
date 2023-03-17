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
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Tools
{
    /**
     * @return mixed
     */
    public static function getRegisteredExtentions()
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
     * @param $class
     */
    public static function registerExtention($class): void
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
                status,base,sourceclass,sourcepid,projektname,longname,shortname,domainname,contact,email,--div--;Benutzer,reduser,redpass,redemail,--div--;Template Konfigurationen,flexinfo
            ' . $config->getAddFields(),
            ];
            $GLOBALS['TCA']['tx_sudhaus7wizard_domain_model_creator']['columns']['sourcepid']['config']['default'] = $config->getSourcePid();
        }
    }

    public static function generateslug($str): ?string
    {
        $str = mb_strtolower(trim((string)$str));
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
        $str = preg_replace('/[^a-z0-9-]/', '-', $str);

        return preg_replace('/-+/', '-', $str);
    }

    public static function array2xml($a)
    {
        /** @var $flexObj FlexFormTools */
        $flexObj = GeneralUtility::makeInstance(FlexFormTools::class);
        return $flexObj->flexArray2Xml($a, true);
    }
}
