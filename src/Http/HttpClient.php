<?php

namespace Infoxchange\MessageMedia\Http;

use Infoxchange\MessageMedia\Exceptions\AuthenticationException;
use Infoxchange\MessageMedia\Exceptions\NotFoundException;
use Infoxchange\MessageMedia\Exceptions\ValidationException;
use Infoxchange\MessageMedia\Exceptions\ApiException;

class HttpClient
{
    /** @var string */
    private $apiKey;

    /** @var string */
    private $apiSecret;

    /** @var string */
    private $baseUrl;

    /** @var bool */
    private $useHmac;

    /** @var int */
    private $timeout;

    /** @var bool */
    private $verifySsl;

    /** @var string|null */
    private $proxyUrl;

    /**
     * @param string $apiKey
     * @param string $apiSecret
     * @param string $baseUrl
     * @param bool $useHmac
     * @param int $timeout
     * @param bool $verifySsl
     * @param string|null $proxyUrl
     */
    public function __construct(
        $apiKey,
        $apiSecret,
        $baseUrl = 'https://api.messagemedia.com/v1',
        $useHmac = false,
        $timeout = 30,
        $verifySsl = true,
        $proxyUrl = null
    ) {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->useHmac = $useHmac;
        $this->timeout = $timeout;
        $this->verifySsl = $verifySsl;
        $this->proxyUrl = $proxyUrl;
    }

    /**
     * @param string $endpoint
     * @param array $query
     * @return array
     */
    public function get($endpoint, array $query = [])
    {
        $url = $this->buildUrl($endpoint, $query);
        return $this->request('GET', $url, null);
    }

    /**
     * @param string $endpoint
     * @param string|null $body
     * @return array
     */
    public function post($endpoint, $body = null)
    {
        $url = $this->buildUrl($endpoint);
        return $this->request('POST', $url, $body);
    }

    /**
     * @param string $endpoint
     * @param string|null $body
     * @return array
     */
    public function put($endpoint, $body = null)
    {
        $url = $this->buildUrl($endpoint);
        return $this->request('PUT', $url, $body);
    }

    /**
     * @param string $endpoint
     * @param string|null $body
     * @return array
     */
    public function patch($endpoint, $body = null)
    {
        $url = $this->buildUrl($endpoint);
        return $this->request('PATCH', $url, $body);
    }

    /**
     * @param string $endpoint
     * @return array
     */
    public function delete($endpoint)
    {
        $url = $this->buildUrl($endpoint);
        return $this->request('DELETE', $url, null);
    }

    /**
     * @param string $method
     * @param string $url
     * @param string|null $body
     * @return array
     * @throws AuthenticationException
     * @throws ValidationException
     * @throws NotFoundException
     * @throws ApiException
     */
    private function request($method, $url, $body = null)
    {
        $curl = curl_init();

        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($curl, CURLOPT_URL, $url);

        $headers = $this->buildHeaders($body);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 10);

        if (!$this->verifySsl) {
            curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
        }

        // Configure proxy if set
        if ($this->proxyUrl !== null && $this->proxyUrl !== '') {
            curl_setopt($curl, CURLOPT_PROXY, $this->proxyUrl);
            curl_setopt($curl, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
        }

        if ($body !== null && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, $body);
        }

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $curlError = curl_error($curl);

        curl_close($curl);

        if ($response === false) {
            throw new ApiException("cURL Error: {$curlError}");
        }

        // Handle empty responses (e.g., 204 No Content)
        if (empty($response)) {
            $this->handleHttpError($httpCode, null);
            return [];
        }

        $data = json_decode($response, true);

        $this->handleHttpError($httpCode, $data);

        return $data ?? [];
    }

    /**
     * @param string $endpoint
     * @param array $query
     * @return string
     */
    private function buildUrl($endpoint, array $query = [])
    {
        $url = $this->baseUrl . '/' . ltrim($endpoint, '/');

        if (!empty($query)) {
            $url .= '?' . http_build_query($query);
        }

        return $url;
    }

    /**
     * @param string|null $body
     * @return array
     */
    private function buildHeaders($body = null)
    {
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
        ];

        $credentials = base64_encode("{$this->apiKey}:{$this->apiSecret}");
        $headers[] = "Authorization: Basic {$credentials}";

        if ($this->useHmac && $body) {
            $signature = hash_hmac('sha256', $body, $this->apiSecret);
            $headers[] = "X-MessageMedia-Signature: {$signature}";
        }

        return $headers;
    }

    /**
     * @param int $httpCode
     * @param array|null $data
     * @throws AuthenticationException
     * @throws ValidationException
     * @throws NotFoundException
     * @throws ApiException
     */
    private function handleHttpError($httpCode, $data = null)
    {
        // Success codes (2xx)
        if ($httpCode >= 200 && $httpCode < 300) {
            return;
        }

        $message = isset($data['message']) ? $data['message'] : 'Unknown error';
        $errors = isset($data['errors']) ? $data['errors'] : [];

        switch ($httpCode) {
            case 401:
            case 403:
                throw new AuthenticationException($message);
            case 404:
                throw new NotFoundException($message);
            case 400:
            case 422:
                throw new ValidationException($errors);
            default:
                throw new ApiException($message, $httpCode);
        }
    }

    /**
     * Set proxy URL for HTTP requests
     *
     * @param string|null $proxyUrl Proxy URL (e.g., 'http://proxy.example.com:8080' or 'http://user:pass@proxy.example.com:8080')
     * @return void
     */
    public function setProxy($proxyUrl)
    {
        $this->proxyUrl = $proxyUrl;
    }

    /**
     * Get current proxy URL
     *
     * @return string|null
     */
    public function getProxy()
    {
        return $this->proxyUrl;
    }
}
