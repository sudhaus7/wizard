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

use SUDHAUS7\Sudhaus7Wizard\Backend\TCA\UpdateStatus;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = UpdateStatus::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][]  = UpdateStatus::class;

ExtensionManagementUtility::addToInsertRecords('tx_sudhaus7wizard_domain_model_creator');

$GLOBALS['TCA']['tx_sudhaus7wizard_domain_model_creator']['ctrl']['security']['ignorePageTypeRestriction'] = true;
