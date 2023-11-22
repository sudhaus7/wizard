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

call_user_func(function (): void {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPlugin(
        [
            'Inline CSV Element',
            'inlinecsvelement',
            'EXT:template/Resources/Public/Icons/Extension.svg',
        ],
        'CType',
        'template'
    );

    $GLOBALS['TCA']['tt_content']['columns']['myrelations'] = [
        'config'=>[
            'type'=>'inline',
            'foreign_table'=>'sys_category',

        ],
    ];

    $GLOBALS['TCA']['tt_content']['types']['inlinecsvelement'] = [

        'showitem' => '
			--palette--;;general,
			--palette--;;header,
			tx_news_related_news,
			myrelations,
			--palette--;;access,
		',
    ];
});
