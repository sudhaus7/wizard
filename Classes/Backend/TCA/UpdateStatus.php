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

namespace SUDHAUS7\Sudhaus7Wizard\Backend\TCA;

use SUDHAUS7\Sudhaus7Wizard\Interfaces\WizardProcessInterface;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationPathDoesNotExistException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class UpdateStatus
{
    /**
     * @param array<string, mixed> $fieldArray
     * @throws ExtensionConfigurationExtensionNotConfiguredException
     * @throws ExtensionConfigurationPathDoesNotExistException
     */
    public function processDatamap_postProcessFieldArray(
        string $status,
        string $table,
        int|string $id,
        array &$fieldArray,
        DataHandler &$pObj
    ): void {
        $globalConf = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('sudhaus7_wizard');
        if ($table == 'tx_sudhaus7wizard_domain_model_creator') {
            if ($status == 'new') {
                $fieldArray['status'] = 0;
            } else {
                $row = BackendUtility::getRecord($table, $id);

                foreach ($fieldArray as $k => $v) {
                    $row[$k] = $v;
                }

                $ret = true;
                $fields = [
                    'base',
                    'sourceclass',
                    'projektname',
                    'longname',
                    'shortname',
                    'domainname',
                    'contact',
                ];
                foreach ($fields as $f) {
                    if (empty($row[$f])) {
                        $ret = false;
                    }
                }

                if (!empty($row['shortname'])) {
                    if ($globalConf['unifyshortname']) {
                        $shortname = str_replace([' ', '-'], ['_', '_'], (string)$row['shortname']);
                        $shortArray = GeneralUtility::trimExplode('_', $shortname, true);
                        if (count($shortArray) == 1) {
                            array_unshift($shortArray, 'BK');
                        }
                        $shortname = strtoupper((string)array_shift($shortArray)) . '_';
                        $shortname .= strtolower(implode('_', $shortArray));
                        $fieldArray['shortname'] = $shortname;
                    } else {
                        $fieldArray['shortname'] = strtolower((string)$row['shortname']);
                    }
                }

                /*
                $sourcePage = BackendUtility::getRecord('pages', $row['sourcepid']);
                // disallowed to copy pages (ext URL, be User Area, spacer, sysfolder, bin)
                // @TODO make dynamic
                if (is_array($sourcePage) && in_array($sourcePage['doktype'], [3, 6, 199, 254, 255])) {
                    $fieldArray['status'] = 5;
                }
                */

                if (
                    $ret
                    && ($row['base'] ?? null) !== null
                    && ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['Sudhaus7Wizard']['registeredTemplateExtentions'][$row['base']] ?? null) !== null
                ) {
                    /** @var class-string $class */
                    $class = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['Sudhaus7Wizard']['registeredTemplateExtentions'][$row['base']];

                    // @todo this seems to make no sense, as the check, even before refactoring, asked that the interface is NOT
                    //       implemented, therefore the method `checkWizardConfig` is not secure to be accessible
                    //       Check, what the logic has to be here and harden the code
                    $implementedClasses = class_implements($class);
                    if ($implementedClasses === false || !in_array(WizardProcessInterface::class, $implementedClasses, true)) {
                        /**
                         * @var WizardProcessInterface $class
                         */
                        $ret = $class::checkWizardConfig($row);
                    }
                }
                if (!$ret) {
                    $fieldArray['status'] = 0;
                }
            }
        }
    }
}
