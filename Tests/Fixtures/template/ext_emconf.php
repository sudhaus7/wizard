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

$EM_CONF['template'] = [
    'title' => '(Sudhaus7) Wizard Test Template',
    'description' => '',
    'category' => 'fe',
    'state' => 'beta',
    'author' => 'Frank Berger',
    'author_email' => 'fberger@b-factor.de',
    'author_company' => 'Sudhaus 7',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-12.4.99',
        ],
        'conflicts' => [
        ],
        'suggests' => [
        ],
    ],
];
