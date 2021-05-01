<?php

namespace App\Rules;

use App\Service\Elliptic;
use Illuminate\Contracts\Validation\Rule;

class RiskScoreRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {

    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $deposit = Deposit::whereHash($value)->first();
        return $deposit && $deposit->risk_score && $deposit->risk_score > Elliptic::RISK_HIGH;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'High risk score.';
    }
}
