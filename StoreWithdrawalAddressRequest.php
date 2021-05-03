<?php
namespace App\Http\Requests;

use App\Rules\RiskScoreRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreWithdrawalAddressRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'currency_code' => '',
            'address' => new RiskScoreRule($this),
            'address_tag' => '',
        ];
    }
}
