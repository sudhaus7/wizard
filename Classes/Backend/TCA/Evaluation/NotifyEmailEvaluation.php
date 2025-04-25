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

class NotifyEmailEvaluation
{
    public function evaluateFieldValue($value, $is_in, &$set)
    {
        $value = preg_replace('/\s*/', '', $value);
        if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            $set = false;
        }
        return $value;
    }

    public function deevaluateFieldValue(array $parameters)
    {

        if (empty($parameters['value']) && isset($GLOBALS['BE_USER']) && !empty($GLOBALS['BE_USER']->user['email'])) {
            return $GLOBALS['BE_USER']->user['email'];
        }
        return $parameters['value'];
    }
}
