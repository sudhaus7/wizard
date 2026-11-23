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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') or die();

(static function (): void {
    $newColumnsArray = [
        'tx_template_related_pages' => [
            'label' => 'Related pages',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectMultipleSideBySide',
                'foreign_table' => 'pages',
                'MM' => 'tx_template_pages_related_mm',
            ],
        ],
    ];

    ExtensionManagementUtility::addTCAcolumns('pages', $newColumnsArray);
    ExtensionManagementUtility::addToAllTCAtypes('pages', 'tx_template_related_pages');
})();
