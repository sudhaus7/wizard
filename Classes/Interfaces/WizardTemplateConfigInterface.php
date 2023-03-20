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

namespace SUDHAUS7\Sudhaus7Wizard\Interfaces;

interface WizardTemplateConfigInterface
{
    /**
     * Returns the extension Name of the template extension
     * for example 'my_template'
     * @return string
     */
    public function getExtension(): string;

    /**
     * Returns a description for this Template
     * For example 'Primary School Template'
     * @return string
     */
    public function getDescription(): string;

    /**
     * Returns the page ID of the page branch to clone
     * @return int|string
     */
    public function getSourcePid(): int|string;

    /**
     * Returns the Path to a Wizard flexform File for additional Template specific Config options
     * for example: EXT:my_template/Flexforms/Wizard.xml
     * @return string
     */
    public function getFlexinfoFile(): string;

    /**
     * returns a comma separeted list of additional fields from the tx_sudhaus7wizard_domain_model_creator record to display (TCA Style)
     * @return string
     */
    public function getAddFields(): string;
}
