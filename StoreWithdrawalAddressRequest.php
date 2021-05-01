<?php
namespace App\Http\Requests;

use App\Rules\RiskScoreRule;
use App\Service\Elliptic;
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
            'address' => '',
            'address_tag' => '',
            'hash' => new RiskScoreRule()
        ];
    }
}
