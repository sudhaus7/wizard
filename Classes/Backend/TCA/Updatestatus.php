<?php

/*
 * This file is part of the TYPO3 project.
 * (c) 2022 B-Factor GmbH
 *          Sudhaus7
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 * The TYPO3 project - inspiring people to share!
 * @copyright 2022 B-Factor GmbH https://b-factor.de/
 * @author Frank Berger <fberger@b-factor.de>
 * @author Daniel Simon <dsimon@b-factor.de>
 */

namespace SUDHAUS7\Sudhaus7Wizard\Backend\TCA;

use SUDHAUS7\Sudhaus7Base\Tools\DB;
use SUDHAUS7\Sudhaus7Wizard\WizardInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Updatestatus
{
    public function processDatamap_postProcessFieldArray($status, $table, $id, &$fieldArray, &$pObj)
    {
        $globalconf = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class)->get('sudhaus7_wizard');
        if ($table == 'tx_sudhaus7wizard_domain_model_creator') {
            if ($status == 'new') {
                $fieldArray['status'] = 0;
            } else {
                $row = DB::getRecord($table, $id);

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
                    if ($globalconf['unifyshortname']) {
                        $s = str_replace([' ', '-'], ['_', '_'], $row['shortname']);
                        $a = GeneralUtility::trimExplode('_', $s);
                        if (count($a) == 1) {
                            array_unshift($a, 'BK');
                        }
                        $s = strtoupper(array_shift($a)) . '_';
                        $s .= strtolower(implode('_', $a));
                        $fieldArray['shortname'] = $s;
                    } else {
                        $fieldArray['shortname'] = strtolower($row['shortname']);
                    }
                }

                $sourcePage = DB::getRecord('pages', $row['sourcepid']);
                // disallowed to copy pages (ext URL, be User Area, spacer, sysfolder, bin)
                if (is_array($sourcePage) && in_array($sourcePage['doktype'], [3, 6, 199, 254, 255])) {
                    $fieldArray['status'] = 5;
                }

                if ($ret && isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['Sudhaus7Wizard']['registeredExtentions'][$row['base']])) {
                    $class = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['Sudhaus7Wizard']['registeredExtentions'][$row['base']];
                    if (in_array('checkConfig', get_class_methods($class))) {
                        /**
                         * @var $class WizardInterface
                         */
                        $ret = $class::checkConfig($row);
                    }
                }
                if (!$ret) {
                    $fieldArray['status'] = 0;
                }
            }
        }
    }
}
