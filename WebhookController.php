<?php

namespace App\Http\Controllers;

use App\Rules\RiskScoreRule;
use App\Service\Elliptic;
use Illuminate\Support\Facades\Validator;

class WebhookController extends Controller
{
    public function store()
    {
        $hash = 'accf5c09cc027339a3beb2e28104ce9f406ecbbd29775b4a1a17ba213f1e035e';
        $address = '15Hm2UEPaEuiAmgyNgd5mF3wugqLsYs3Wn';

        $params = ['hash' => $hash, 'address' => $address];

        $elliptic = new Elliptic();
        $elliptic->setParams($params);
//        $elliptic->getRiskRules();
        $elliptic->synchronous();

        $validator = Validator::make($params, [
            'hash' => new RiskScoreRule($elliptic)
        ]);

        if ($validator->fails()) {
            return $validator->errors();
        }

        return 'Success';
    }
}
