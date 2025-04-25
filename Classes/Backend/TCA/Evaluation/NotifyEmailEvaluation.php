<?php
declare( strict_types=1 );


namespace SUDHAUS7\Sudhaus7Wizard\Backend\TCA\Evaluation;

class NotifyEmailEvaluation {
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
