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

use SUDHAUS7\Sudhaus7Wizard\Tools;

$GLOBALS['TCA']['tx_sudhaus7wizard_domain_model_log'] = Tools::getTcaConfiguration()->configureSearchFields(
    $GLOBALS['TCA']['tx_sudhaus7wizard_domain_model_log'],
    [
        'creator',
        'level',
        'message',
        'context',
    ]
);
