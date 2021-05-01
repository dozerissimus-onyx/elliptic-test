<?php


namespace App\Service;

use GuzzleHttp\Client;


class Elliptic
{
    const RISK_HIGH = 5;

    const TYPE_SOURCE = 'source_of_funds';
    const TYPE_DESTINATION = 'destination_of_funds';

    /**
     * @var Client $client
     */
    protected $client;

    /**
     * @var array $params
     */
    protected $params;

    /**
     * @var float $riskScore
     */
    protected $riskScore;

    /**
     * @var array $headers
     */
    protected $headers;

    /**
     * @var array $payload
     */
    protected $payload;

    /**
     * @var string uri
     */
    protected $uri;

    /**
     * @var string $method
     */
    protected $method;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => config('elliptic.baseUri') //https://aml-api.elliptic.co
        ]);
    }

    /**
     * @param array $params
     */
    public function setParams(array $params) {
        $this->params = $params;
    }

    /**
     * Make request and give risk score
     */
    public function synchronous() {
        $this->method = 'POST';
        $this->uri = '/v2/analyses/synchronous';
        $this->payload = [
            "customer_reference" => $this->params['customer'] ?? 'testCustomer',
            "subject" => [
                "asset" => strtoupper($this->params['asset'] ?? 'BTC'),
                "hash" => $this->params['hash'] ?? '',
                "output_address" => $this->params['address'] ?? '',
                "output_type" => "address",
                "type" => "transaction"
            ],
            "type" => $this->params['type'] ?? self::TYPE_DESTINATION
        ];

        $response = $this->request();
        $this->riskScore = $response['risk_score'] ?? null;
    }

    /**
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getRiskRules()
    {
        $this->method = 'GET';
        $this->uri = '/v2/risk_rules';
        return $this->request();
    }

    /**
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    protected function request() {
        $ts = time() * 1000;

        $signature = $this->getSignature(config('elliptic.secret'), $ts, $this->method, $this->uri, $this->payload ? json_encode($this->payload) : '{}');

        $headers = [
            'Content-Type' => 'application/json',
            'x-access-key' => config('elliptic.key'),
            'x-access-sign' => $signature,
            'x-access-timestamp' => $ts
        ];

        $response = $this->client->request($this->method, $this->uri, [
            'headers' => $headers,
            'json' => $this->payload,
        ]);

        return json_decode($response->getBody(), true);
    }

    /**
     * @return float
     */
    public function getRiskScore() {
        return $this->riskScore;
    }

    /**
     * @param string $secret
     * @param int $ts
     * @param string $httpMethod
     * @param string $httpPath
     * @param string $payload
     * @return string
     */
    protected function getSignature($secret, $ts, $httpMethod, $httpPath, $payload) {
        $request_text = $ts . $httpMethod . strtolower($httpPath) . $payload;
        $hash = hash_hmac('sha256', $request_text, base64_decode($secret), true);

        return base64_encode($hash);
    }
}

