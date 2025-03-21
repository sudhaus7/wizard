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

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

if (!defined('TYPO3')) {
    die('Access denied.');
}

return [
    'ctrl' => [
        'title'             => 'LLL:EXT:sudhaus7_wizard/Resources/Private/Language/locallang.xlf:tx_sudhaus7wizard_domain_model_log',
        'label' => 'message',
        'label_alt'=>'tstamp,level',
        'label_alt_force'=>1,
        'rootLevel' => -1,
        'adminOnly'=> true,
        'default_sortby'=>'tstamp',
        'hideTable'=>true,
        'security' => [
            'ignorePageTypeRestriction' => true,
            'ignoreRootLevelRestriction' => true,
            'ignoreWebMountRestriction' => true,
        ],
        //'descriptionColumn'=>'message',
        'tstamp'            => 'tstamp',
        'crdate'            => 'crdate',
        'searchFields' => 'creator,level,message,context',
        'dynamicConfigFile' => ExtensionManagementUtility::extPath('sudhaus7_wizard') . 'Configuration/TCA/tx_sudhaus7wizard_domain_model_log.php',
        'iconfile' => 'EXT:sudhaus7_wizard/Resources/Public/Icons/icon.svg',
    ],
    'types' => [
        '1' => ['showitem' => 'level,message,context'],
    ],
    'palettes' => [
        //'1' => Array('showitem' => 'hidden,sys_language_uid,t3ver_label,l10n_parent'),
    ],
    'columns' => [
        'message' => [
            'label' => 'Email-Vorlage',
            'config' => [
                'type' => 'text',
            ],
        ],
        'context' => [
            'label' => 'Email-Vorlage',
            'config' => [
                'type' => 'text',
            ],
        ],

        'level' => [
            'label' => 'Level',
            'config' => [
                'type' => 'input',
            ],
        ],

    ],
];
