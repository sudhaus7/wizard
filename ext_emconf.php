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

$EM_CONF[$_EXTKEY] = [
    'title' => '(Sudhaus7) Wizard',
    'description' => '',
    'category' => 'fe',
    'state' => 'beta',
    'author' => 'Frank Berger',
    'author_email' => 'fberger@b-factor.de',
    'author_company' => 'Sudhaus 7',
    'version' => '0.5.13',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-13.4.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],

];
