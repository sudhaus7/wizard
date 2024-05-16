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

namespace SUDHAUS7\Sudhaus7Wizard\Events;

use SUDHAUS7\Sudhaus7Wizard\CreateProcess;
use SUDHAUS7\Sudhaus7Wizard\WizardProcess\WizardProcessInterface;

interface WizardEventInterface
{
    /**
     * @return string the extension key of the running template
     */
    public function getExtensionKey(): string;

    /**
     * @return CreateProcess the create process running
     */
    public function getCreateProcess(): CreateProcess;

    /**
     * @return WizardProcessInterface
     */
    public function getTemplateConfig(): WizardProcessInterface;
}
