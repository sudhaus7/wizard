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

namespace SUDHAUS7\Sudhaus7Wizard\Backend\TCA\Types;

use SUDHAUS7\Sudhaus7Wizard\Sources\RestWizardServerSource;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class RemoteSites
{
    public function itemsProcFunc(&$params)
    {
        if (!empty($params['row']['sourceclass'])) {
            $class = $params['row']['sourceclass'];
            if (\class_exists($class)) {
                $sourceObj = GeneralUtility::makeInstance(trim($class, '\\'));
                if ($sourceObj instanceof RestWizardServerSource) {
                    $sites           = $sourceObj->getSites();
                    usort($sites, function ($a, $b) {
                        return strtolower($a['title']) <=> strtolower($b['title']);
                    });
                    $params['items'] = [];
                    foreach ($sites as $site) {
                        $params['items'][] = [ $site['title'], $site['uid'] ];
                    }
                }
            }
        }
    }
}
