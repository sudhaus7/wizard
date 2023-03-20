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

if (\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded('sudhaus7_wizard')) {
    \SUDHAUS7\Sudhaus7Wizard\Tools::registerWizardProcess(\SUDHAUS7\Sudhaus7Template\Wizard\WizardProcess::class);
}
