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

use SUDHAUS7\Sudhaus7Template\Wizard\WizardProcess;
use SUDHAUS7\Sudhaus7Template\Wizard\WizardProcessRemote;
use SUDHAUS7\Sudhaus7Wizard\Tools;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

call_user_func(function (): void {
    if (ExtensionManagementUtility::isLoaded('sudhaus7_wizard')) {
        Tools::registerWizardProcess(WizardProcess::class);
        Tools::registerWizardProcess(WizardProcessRemote::class);
    }
});
