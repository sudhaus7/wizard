<?php

declare(strict_types=1);

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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass'][] = SUDHAUS7\Sudhaus7Wizard\Backend\TCA\Updatestatus::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processCmdmapClass'][]  = SUDHAUS7\Sudhaus7Wizard\Backend\TCA\Updatestatus::class;

ExtensionManagementUtility::addToInsertRecords('tx_sudhaus7wizard_domain_model_creator');
ExtensionManagementUtility::allowTableOnStandardPages('tx_sudhaus7wizard_domain_model_creator');
