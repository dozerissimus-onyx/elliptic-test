<?php

namespace App\Http\Controllers;

use App\Rules\RiskScoreRule;
use App\Service\Elliptic;
use Illuminate\Support\Facades\Validator;

class WebhookController extends Controller
{
    public function store()
    {
		//example data from app.elliptic.co
        $exampleHash = 'accf5c09cc027339a3beb2e28104ce9f406ecbbd29775b4a1a17ba213f1e035e';
        $exampleAddress = '15Hm2UEPaEuiAmgyNgd5mF3wugqLsYs3Wn';

        $params = ['hash' => $exampleHash, 'address' => $exampleAddress];

        $elliptic = new Elliptic();
        $elliptic->setParams($params);
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
