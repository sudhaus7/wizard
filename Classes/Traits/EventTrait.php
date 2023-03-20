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

namespace SUDHAUS7\Sudhaus7Wizard\Traits;

use SUDHAUS7\Sudhaus7Wizard\CreateProcess;
use SUDHAUS7\Sudhaus7Wizard\Interfaces\WizardProcessInterface;

trait EventTrait
{
    protected CreateProcess $create_process;
    public function getExtensionKey(): string
    {
        return $this->getCreateProcess()->getTemplatekey() ?? $this->getTemplateConfig()->getWizardConfig()->getExtension();
    }
    public function getCreateProcess(): CreateProcess
    {
        return $this->create_process;
    }
    public function getTemplateConfig(): WizardProcessInterface
    {
        return $this->getCreateProcess()->getTemplate();
    }
}
