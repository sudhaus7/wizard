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

if (!defined('TYPO3')) {
    die('Access denied.');
}

return [

    'ctrl' => [
        'title'             => 'LLL:EXT:sudhaus7_wizard/Resources/Private/Language/locallang.xlf:tx_sudhaus7wizard_domain_model_creator',
        'label' => 'projektname',
        'rootLevel' => -1,
        'tstamp'            => 'tstamp',
        'crdate'            => 'crdate',
        'cruser_id'         => 'cruser_id',
        'delete'            => 'deleted',
        'enablecolumns'     => [
            'disabled' => 'hidden',
        ],
        'type'=>'base',
        'searchFields' => 'projektname,longname,domainname,',
        'dynamicConfigFile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('sudhaus7_wizard') . 'Configuration/TCA/tx_sudhaus7wizard_domain_model_creator.php',
        'iconfile' => 'EXT:sudhaus7_wizard/Resources/Public/Icons/icon.svg',
    ],
    'types' => [
        '1' => ['showitem' => 'base'],
    ],
    'palettes' => [
        //'1' => Array('showitem' => 'hidden,sys_language_uid,t3ver_label,l10n_parent'),
    ],
    'columns' => [

        't3ver_label' => [
            'displayCond' => 'FIELD:t3ver_label:REQ:true',
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.versionLabel',
            'config' => [
                'type'=>'none',
                'cols' => 27,
            ],
        ],
        'hidden' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_general.xlf:LGL.hidden',
            'config' => [
                'type' => 'check',
            ],
        ],

        'sourcepid' => [
            'displayCond' => 'FIELD:status:<:10',
            'label' => 'LLL:EXT:sudhaus7_wizard/Resources/Private/Language/locallang.xlf:tx_sudhaus7wizard_domain_model_creator.source',
            'config' => [
                'eval'=>'trim,required',
                'type' => 'input',
                'softref'=>'typo3link',
                'default' => '',
                'size' => 50,
                'renderType' => 'inputLink',
                'fieldControl' => ['linkPopup' => ['options' => ['title' => 'LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:header_link_formlabel']]],

            ],
        ],

        'base'=>[
            'label' => 'LLL:EXT:sudhaus7_wizard/Resources/Private/Language/locallang.xlf:tx_sudhaus7wizard_domain_model_creator.base',

            'displayCond' => 'FIELD:status:<:10',
            'config'=>[
                'type'=>'select',
                'renderType' => 'selectSingle',
                'default'=>'1',
                'items'=> [
                    ['Bitte wählen', 1],
                ],
            ],
            'onChange' => 'reload',
        ],

        'sourceclass'=>[
            'label' => 'LLL:EXT:sudhaus7_wizard/Resources/Private/Language/locallang.xlf:tx_sudhaus7wizard_domain_model_creator.sourcetype',
            'exclude' => 1,
            'displayCond' => 'FIELD:status:<:10',
            'config'=>[
                'type'=>'select',
                'renderType' => 'selectSingle',
                'default'=>'\\' . \SUDHAUS7\Sudhaus7Wizard\Sources\Localdatabase::class,
                'items'=> [
                    ['LLL:EXT:sudhaus7_wizard/Resources/Private/Language/locallang.xlf:tx_sudhaus7wizard_domain_model_creator.sourcetype.localdb', '\\' . \SUDHAUS7\Sudhaus7Wizard\Sources\Localdatabase::class],
                    //['Umzugs-service', '\\' . \SUDHAUS7\Sudhaus7Wizard\Sources\Couchdb::class],
                ],
            ],
        ],

        'projektname' => [
            'exclude' => 0,
            'displayCond' => 'FIELD:status:<:10',
            'label' => 'LLL:EXT:sudhaus7_wizard/Resources/Private/Language/locallang.xlf:tx_sudhaus7wizard_domain_model_creator.projektname',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,required',
            ],
        ],
        'longname' => [
            'exclude' => 0,

            'displayCond' => 'FIELD:status:<:10',
            'label' => 'LLL:EXT:sudhaus7_wizard/Resources/Private/Language/locallang.xlf:tx_sudhaus7wizard_domain_model_creator.longname',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,required',
            ],
        ],
        'shortname' => [
            'exclude' => 0,

            'displayCond' => 'FIELD:status:<:10',
            'label' => 'LLL:EXT:sudhaus7_wizard/Resources/Private/Language/locallang.xlf:tx_sudhaus7wizard_domain_model_creator.shortname',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,required',
            ],
        ],
        'domainname' => [
            'exclude' => 0,

            'displayCond' => 'FIELD:status:<:10',
            'label' => 'LLL:EXT:sudhaus7_wizard/Resources/Private/Language/locallang.xlf:tx_sudhaus7wizard_domain_model_creator.domainname',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,required',
            ],
        ],
        'contact' => [
            'exclude' => 0,

            'displayCond' => 'FIELD:status:<:10',
            'label' => 'LLL:EXT:sudhaus7_wizard/Resources/Private/Language/locallang.xlf:tx_sudhaus7wizard_domain_model_creator.contact',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,email,required',
            ],
        ],

        'reduser' => [
            'exclude' => 0,

            'displayCond' => 'FIELD:status:<:10',
            'label' => 'LLL:EXT:sudhaus7_wizard/Resources/Private/Language/locallang.xlf:tx_sudhaus7wizard_domain_model_creator.reduser',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,required',
            ],
        ],
        'redemail' => [
            'exclude' => 0,

            'displayCond' => 'FIELD:status:<:10',
            'label' => 'LLL:EXT:sudhaus7_wizard/Resources/Private/Language/locallang.xlf:tx_sudhaus7wizard_domain_model_creator.redemail',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,email,required',
            ],
        ],
        'redpass' => [
            'exclude' => 0,

            'displayCond' => 'FIELD:status:<:10',
            'label' => 'LLL:EXT:sudhaus7_wizard/Resources/Private/Language/locallang.xlf:tx_sudhaus7wizard_domain_model_creator.redpass',
            'config' => [
                'type' => 'input',
                'size' => 30,
                'eval' => 'trim,required',
            ],
        ],
        'status' => [
            'exclude' => 0,
            'label' => 'LLL:EXT:sudhaus7_wizard/Resources/Private/Language/locallang.xlf:tx_sudhaus7wizard_domain_model_creator.status',
            'config'=>[
                'default' => '0',
                'type'=>'select',
                'renderType' => 'selectSingle',
                'items' => \SUDHAUS7\Sudhaus7Wizard\Domain\Model\Creator::getStatusTca(),
            ],
        ],

        'flexinfo' => [
            'displayCond' => 'FIELD:status:<:10',
            'label'=>'LLL:EXT:sudhaus7_wizard/Resources/Private/Language/locallang.xlf:tx_sudhaus7wizard_domain_model_creator.flexinfo',
            'config'=>[
                'ds'=>[
                    'default'=>'FILE:EXT:sudhaus7_wizard/Configuration/Flexforms/Wizard.xml',
                ],
                'ds_pointerField'=>'base',
                'type'=>'flex',
            ],
        ],

        'email' => [
            'displayCond' => 'FIELD:status:=:20',
            'label' => 'Email-Vorlage',
            'config' => [
                'type' => 'text',
            ],
        ],
        'pid' => [
            'exclude' => 1,
            'label' => 'PID',
            'config' => [
                'type' => 'none',
            ],
        ],
        'cruser_id' => [
            'exclude' => 1,
            'label' => 'cruser_id',
            'config' => [
                'type' => 'none',
            ],
        ],

    ],
];
