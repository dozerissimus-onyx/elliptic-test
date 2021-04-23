<?php


namespace App\Service;

use GuzzleHttp\Client;


class Elliptic
{
    const RISK_HIGH = 5;

    protected $client;
    protected $params;
    protected $riskScore;
    protected $headers;
    protected $payload;
    protected $uri;
    protected $method;
    protected $baseUri = 'https://aml-api.elliptic.co';

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => $this->baseUri
        ]);
    }

    public function setParams(array $params) {
        $this->params = $params;
    }

    public function synchronous() {
        $this->method = 'POST';
        $this->uri = '/v2/analyses/synchronous';
        $this->payload = [
            "customer_reference" => "string",
            "subject" => [
                "asset" => "BTC",
                "hash" => $this->params['hash'] ?? '',
                "output_address" => $this->params['address'] ?? '',
                "output_type" => "address",
                "type" => "transaction"
            ],
            "type" => "destination_of_funds"
        ];

        $response = $this->request();
        $this->riskScore = $response['risk_score'] ?? null;
    }

    public function getRiskRules()
    {
        $this->method = 'GET';
        $this->uri = '/v2/risk_rules';
        $response = $this->request();
    }

    protected function request() {
        $ts = time() * 1000;

        $signature = $this->getSignature(env('ELLIPTIC_SECRET'), $ts, $this->method, $this->uri, $this->payload ? json_encode($this->payload) : '{}');

        $headers = [
            'Content-Type' => 'application/json',
            'x-access-key' => env('ELLIPTIC_KEY'),
            'x-access-sign' => $signature,
            'x-access-timestamp' => $ts
        ];

        $response = $this->client->request($this->method, $this->uri, [
            'headers' => $headers,
            'json' => $this->payload,
        ]);

        return json_decode($response->getBody(), true);
    }

    public function getRiskScore() {
        return $this->riskScore;
    }

    protected function getSignature($secret, $ts, $httpMethod, $httpPath, $payload) {
        $request_text = $ts . $httpMethod . strtolower($httpPath) . $payload;
        $hash = hash_hmac('sha256', $request_text, base64_decode($secret), true);

        return base64_encode($hash);
    }
}

