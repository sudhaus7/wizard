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

namespace SUDHAUS7\Sudhaus7Wizard\WizardProcess;

use SUDHAUS7\Sudhaus7Wizard\CreateProcess;
use SUDHAUS7\Sudhaus7Wizard\Interfaces\WizardTemplateConfigInterface;

interface WizardProcessInterface
{
    /**
     * @return WizardTemplateConfigInterface
     */
    public static function getWizardConfig(): WizardTemplateConfigInterface;

    /**
     * Checks if the template has been configured correctly
     * @param array<array-key, mixed> $data
     * @return bool
     */
    public static function checkWizardConfig(array $data): bool;

    /**
     * Returns the be_users Record for the template Backend User to clone
     * @return array<array-key, mixed>
     */
    public function getTemplateBackendUser(CreateProcess $pObj): array;

    /**
     * Returns the be_groups record for the template backend group to clone
     * @return array<array-key, mixed>
     */
    public function getTemplateBackendUserGroup(CreateProcess $pObj): array;

    /**
     * Returns the base directory under 1:fileadmin where to create the new Sites folder structure
     * for example 'oursites/primaryschools/' - translating to '1:fileadmin/oursites/primaryschools'
     * @return string
     */
    public function getMediaBaseDir(): string;

    /**
     * @param CreateProcess $pObj
     */
    public function finalize(CreateProcess &$pObj): void;
}
