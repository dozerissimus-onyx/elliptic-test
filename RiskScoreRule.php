<?php

namespace App\Rules;

use App\Service\Elliptic;
use Illuminate\Contracts\Validation\Rule;

class RiskScoreRule implements Rule
{
    protected $request;
    protected $elliptic;

    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct($request)
    {
        $this->request = $request;
        $this->elliptic = new Elliptic();
        $this->elliptic->setParams([
            'address' => $request->address,
            'asset' => $request->currency_code
        ]);
        $this->elliptic->walletSynchronous();
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
        return $this->elliptic->getRiskScore() && $this->elliptic->getRiskScore() > Elliptic::RISK_HIGH;
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
