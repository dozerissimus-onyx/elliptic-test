<?php

namespace App\Service;

use GuzzleHttp;
use GuzzleHttp\Psr7\MultipartStream;
use Illuminate\Support\Facades\Storage;


class SumSub
{
    protected $applicant;

    const ANSWER_GREEN = 'GREEN';

    public static $docTypes = [
        'ID_CARD' => 'national_identity_card',
        'PASSPORT' => 'passport',
        'DRIVERS' => 'driving_licence',
        'RESIDENCE_PERMIT' => 'residence_permit'
    ];

    public function createApplicant($externalUserId)
    {
        $requestBody = [
            'externalUserId' => $externalUserId
        ];

        $url = '/resources/applicants?levelName=basic-kyc-level';
        $request = new GuzzleHttp\Psr7\Request('POST', config('sumsub.base_url') . $url);
        $request = $request->withHeader('Content-Type', 'application/json');
        $request = $request->withBody(GuzzleHttp\Psr7\stream_for(json_encode($requestBody)));

        $responseBody = $this->sendHttpRequest($request, $url)->getBody();
        return json_decode($responseBody)->{'id'};
    }

    public function sendHttpRequest($request, $url, $params = [])
    {
        $client = new GuzzleHttp\Client();
        $ts = time();

        $request = $request->withHeader('X-App-Token', config('sumsub.app-token'));
        $request = $request->withHeader('X-App-Access-Sig', $this->createSignature($ts, $request->getMethod(), $url, $request->getBody()));
        $request = $request->withHeader('X-App-Access-Ts', $ts);

        // Reset stream offset to read body in `send` method from the start
        $request->getBody()->rewind();

        try {
            $response = $client->send($request);
            if ($response->getStatusCode() != 200 && $response->getStatusCode() != 201) {
                // https://developers.sumsub.com/api-reference/#errors
                // If an unsuccessful answer is received, please log the value of the "correlationId" parameter.
                // Then perhaps you should throw the exception. (depends on the logic of your code)
            }
        } catch (GuzzleHttp\Exception\GuzzleException $e) {
            error_log($e);
        }

        return $response;
    }

    private function createSignature($ts, $httpMethod, $url, $httpBody)
    {
        return hash_hmac('sha256', $ts . strtoupper($httpMethod) . $url . $httpBody, config('sumsub.secret'));
    }

    public function addDocument($params)
        // https://developers.sumsub.com/api-reference/#adding-an-id-document
    {
        $metadata = ['idDocType' => $params['docType'], 'country' => $params['country']];
        $file = $params['fileName'];

        $multipart = new MultipartStream([
            [
                "name" => "metadata",
                "contents" => json_encode($metadata)
            ],
            [
                'name' => 'content',
                'contents' => fopen($file, 'r')
            ],
        ]);

        $url = "/resources/applicants/" . $params['applicantId'] . "/info/idDoc";
        $request = new GuzzleHttp\Psr7\Request('POST', config('sumsub.base_url') . $url);
        $request = $request->withBody($multipart);

        return $this->sendHttpRequest($request, $url)->getHeader("X-Image-Id")[0];
    }

    public function getApplicantStatus($applicantId)
        // https://developers.sumsub.com/api-reference/#getting-applicant-status-api
    {
        $url = "/resources/applicants/" . $applicantId . "/requiredIdDocsStatus";
        $request = new GuzzleHttp\Psr7\Request('GET', config('sumsub.base_url') . $url);

        $response =  $this->sendHttpRequest($request, $url);
        return json_decode($response->getBody());
    }

    public function getAccessToken($externalUserId)
        // https://developers.sumsub.com/api-reference/#access-tokens-for-sdks
    {
        $url = "/resources/accessTokens?userId=" . $externalUserId;
        $request = new GuzzleHttp\Psr7\Request('POST', config('sumsub.base_url') . $url);

        return $this->sendHttpRequest($request, $url)->getBody();
    }

    protected function getShareToken($applicantId, $clientId) {
        $url = "/resources/accessTokens/-/shareToken?applicantId={$applicantId}&forClientId={$clientId}";
        $request = new GuzzleHttp\Psr7\Request('POST', config('sumsub.base_url') . $url);

        $response = json_decode($this->sendHttpRequest($request, $url)->getBody(), true);
        return $response['token'];
    }

    protected function importApplicant($shareToken) {
        $url = "/resources/applicants/-/import?shareToken={$shareToken}";
        $request = new GuzzleHttp\Psr7\Request('POST', config('sumsub.base_url') . $url);

        return json_decode($this->sendHttpRequest($request, $url)->getBody(), true);
    }

    public function shareApplicant($applicantId, $clientId) {
        $token = $this->getShareToken($applicantId, $clientId);

        return $this->importApplicant($token);
    }

    public function downloadImage($inspectionId, $imageId) {
        $url = "/resources/inspections/{$inspectionId}/resources/{$imageId}]";
        $request = new GuzzleHttp\Psr7\Request('GET', config('sumsub.base_url') . $url);

        $path = "{$inspectionId}/docs/{$imageId}";

        Storage::put($path, $request->getBody(), 'public');

        return Storage::url($path);
    }
}
