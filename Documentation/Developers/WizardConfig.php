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

use SUDHAUS7\Sudhaus7Wizard\Interfaces\WizardTemplateConfigInterface;

class WizardConfig implements WizardTemplateConfigInterface
{
    public function getExtension(): string
    {
        return 'template';
    }

    public function getDescription(): string
    {
        return 'Introduction Package';
    }

    public function getSourcePid(): int|string
    {
        return 1;
    }

    public function getFlexinfoFile(): string
    {
        return 'FILE:EXT:template/Configuration/Flexforms/Wizard.xml';
    }

    public function getAddFields(): string
    {
        return '';
    }
}
