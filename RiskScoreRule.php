<?php

namespace App\Rules;

use App\Service\Elliptic;
use Illuminate\Contracts\Validation\Rule;

class RiskScoreRule implements Rule
{
    private $model;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($model)
    {
        $this->model = $model;
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
        return $this->model->getRiskScore() && $this->model->getRiskScore() < Elliptic::RISK_HIGH;
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
