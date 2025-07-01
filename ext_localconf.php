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

use SUDHAUS7\Sudhaus7Wizard\Backend\TCA\Evaluation\DomainnameEvaluation;
use SUDHAUS7\Sudhaus7Wizard\Backend\TCA\Evaluation\NotifyEmailEvaluation;
use SUDHAUS7\Sudhaus7Wizard\Backend\TCA\Types\CreatorLogRenderType;

$GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['nodeRegistry'][1742575212]                = [
    'nodeName' => 'creatorLog',
    'priority' => 10,
    'class' => CreatorLogRenderType::class,
];
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][ DomainnameEvaluation::class] = '';

$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][ NotifyEmailEvaluation::class] = '';
