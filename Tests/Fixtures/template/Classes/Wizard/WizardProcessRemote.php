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

namespace SUDHAUS7\Sudhaus7Template\Wizard;

use SUDHAUS7\Sudhaus7Wizard\CreateProcess;
use SUDHAUS7\Sudhaus7Wizard\Interfaces\WizardProcessInterface;
use SUDHAUS7\Sudhaus7Wizard\Interfaces\WizardTemplateConfigInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;

class WizardProcessRemote implements WizardProcessInterface
{
    public static function getWizardConfig(): WizardTemplateConfigInterface
    {
        return new WizardConfig();
    }

    /**
     * @param array<array-key, mixed> $data
     */
    public static function checkWizardConfig(array $data): bool
    {
        return true;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getTemplateBackendUser(CreateProcess $pObj): array
    {
        return BackendUtility::getRecord('be_users', 3) ?? [];
    }

    /**
     * @return array<array-key, mixed>
     */
    public function getTemplateBackendUserGroup(CreateProcess $pObj): array
    {
        return BackendUtility::getRecord('be_groups', 4) ?? [];
    }

    public function getMediaBaseDir(): string
    {
        return 'sites/';
    }

    public function finalize(CreateProcess &$pObj): void {}
}
