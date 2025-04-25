<?php
declare( strict_types=1 );


namespace SUDHAUS7\Sudhaus7Wizard\Backend\TCA\Evaluation;

class DomainnameEvaluation
{
    public function evaluateFieldValue($value, $is_in, &$set)
    {
        $value = preg_replace('/\s*/', '', $value);
        return $value;
    }

}
