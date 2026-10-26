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

use SUDHAUS7\Sudhaus7Wizard\Tools;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

ExtensionManagementUtility::addToInsertRecords('tx_sudhaus7wizard_domain_model_creator');

$GLOBALS['TCA']['tx_sudhaus7wizard_domain_model_creator']['ctrl']['security']['ignorePageTypeRestriction'] = true;

$GLOBALS['TCA']['tx_sudhaus7wizard_domain_model_creator'] = Tools::getTcaConfiguration()->configureSearchFields(
    $GLOBALS['TCA']['tx_sudhaus7wizard_domain_model_creator'],
    [
        'projektname',
        'longname',
        'domainname',
    ]
);
$GLOBALS['TCA']['tx_sudhaus7wizard_domain_model_creator'] = Tools::getTcaConfiguration()->setDefaultFlexDataStructure(
    $GLOBALS['TCA']['tx_sudhaus7wizard_domain_model_creator'],
    'flexinfo',
    'FILE:EXT:sudhaus7_wizard/Configuration/Flexforms/Wizard.xml'
);
