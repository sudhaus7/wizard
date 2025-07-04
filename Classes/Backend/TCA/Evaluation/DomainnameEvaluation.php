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

namespace SUDHAUS7\Sudhaus7Wizard\Backend\TCA\Evaluation;

class DomainnameEvaluation
{
    public function evaluateFieldValue($value, $is_in, &$set)
    {
        $value = preg_replace('/\s*/', '', $value);
        return $value;
    }
}
