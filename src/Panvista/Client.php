<?php
/**
 * Copyright 2013 Panvista Corp.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */

namespace Panvista;

require_once __DIR__ . DIRECTORY_SEPARATOR . 'Exception.php';

class Client
{
    /**
     * @var string $_clientToken The client token
     * @access private
     */
    private $_clientToken;

    /**
     * @var string $_clientSecret The client secret token
     * @access private
     */
    private $_clientSecret;

    /**
     * @var string $_apiUrl The url to the Panvista API
     * @access private
     */
    private $_apiUrl = 'https://api.panvistamobile.com';

    /**
     * @var string $_apiVersion The version of the API to use
     * @access private
     */
    private $_apiVersion = 'v1';

    /**
     * Class constructor
     *
     * @param string $clientToken (Optional)
     * @param string $clientSecret (Optional)
     * @access public
     * @throws \Panvista\Exception
     */
    public function __construct($clientToken = null, $clientSecret = null)
    {
        $this->_clientToken = $clientToken;
        $this->_clientSecret = $clientSecret;
    }

    /**
     * Set the client token
     *
     * @param string $clientToken
     * @access public
     * @return Panvista\Api
     */
    public function setClientToken($clientToken)
    {
        $this->_clientToken = $clientToken;
        return $this;
    }

    /**
     * Get the client token
     *
     * @access public
     * @return string
     */
    public function getClientToken()
    {
        return $this->_clientToken;
    }

    /**
     * Set the client secret
     *
     * @param string $clientSecret
     * @access public
     * @return Panvista\Api
     */
    public function setClientSecret($clientSecret)
    {
        $this->_clientSecret = $clientSecret;
        return $this;
    }

    /**
     * Get the client secret
     *
     * @access public
     * @return string
     */
    public function getClientSecret()
    {
        return $this->_clientSecret;
    }

    /**
     * Set the API url
     *
     * @param string $apiUrl
     * @access public
     * @return Panvista\Api
     */
    public function setApiUrl($apiUrl)
    {
        $this->_apiUrl = $apiUrl;
    }

    /**
     * Get the API URL
     *
     * @access public
     * @return string
     */
    public function getApiUrl()
    {
        return $this->_apiUrl;
    }

    /**
     * Call the API
     *
     * @param string $endpoint The API endpoit eg: /users/list/
     * @param string $method (Optional) The request method
     * @param array $data (Optional) Any data to pass through to the API
     * @param string $contentType (Optional) The content-type of the request
     * @access public
     * @throws \Panvista\Exception
     * @return Array An array of the json response
     */
    public function call($endpoint, $method = 'GET', $data = array(), $contentType = null)
    {
        if (substr($endpoint, 0, 1) == '/') {
            $endpoint = substr($endpoint, 1);
        }

        $requestUrl = $this->getRequestUrl($endpoint, $method);
        list($responseCode, $response) = $this->_sendRequest($requestUrl, $method, $data, $contentType);
        $result = json_decode($response, true);

        if ($responseCode >= 200 && $responseCode < 300) {
            return $result;
        }

        $errorMsg = isset($result->detail) ? $result->detail : (isset($result->errors) ? $result->errors : $response);

        if (is_array($errorMsg)) {
            $errorMsg = implode(', ', $errorMsg);
        }

        switch ($responseCode) {
            case 400:
                $e = new \Panvista\BadRequest($errorMsg, $code = $responseCode);
                break;
            case 403:
                $e = new \Panvista\AuthenticationFailed($errorMsg, $code = $responseCode);
                break;
            case 404:
                $e = new \Panvista\NotFound($errorMsg, $code = $responseCode);
                break;
            case 503:
                $e = new \Panvista\ServiceInactive($errorMsg, $code = $responseCode);
                break;
            default:
                $e = new \Panvista\Exception($errorMsg, $code = $responseCode);
                break;
        }

        throw $e;
    }

    /**
     * Send a request to the API service
     *
     * @param string $requestUrl
     * @param string $method
     * @param array $data
     * @param string $contentType
     * @throws \Panvista\Exception
     * @return Array
     */
    protected function _sendRequest($requestUrl, $method, $data, $contentType)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_URL, $requestUrl);
        curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . '/cacert.pem');

        $headers = array();

        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }

        if ($contentType) {
            $headers[] = 'Content-Type: ' . $contentType;

            if ($data && is_string($data)) {
                $headers[] = 'Content-Length: ' . strlen($data);
            }

            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Panvista\Exception($error);
        }

        $responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return array($responseCode, $response);
    }

    /**
     * Get the request url with the authentication details
     *
     * @param string $endpoint The API endpoint
     * @param unknown $method The request method
     * @access public
     * @return string
     */
    public function getRequestUrl($endpoint, $method)
    {
        $clientToken = $this->getClientToken();

        if (!$clientToken) {
            return sprintf('%s/%s/%s', $this->_apiUrl, $this->_apiVersion, $endpoint);
        }

        $seperator = stripos($endpoint, '?') === false ? '?' : '&';
        $nonce = $this->_generateNonce();
        $timestamp = time();
        $requestUrl = sprintf('/%s/%s%snonce=%s&timestamp=%s', $this->_apiVersion, $endpoint, $seperator, $nonce, $timestamp);
        $signature = $this->_generateSignature($method, $nonce, $timestamp, $requestUrl);
        return sprintf('%s%s&access_token=%s&signature=%s', $this->_apiUrl, $requestUrl, $clientToken, $signature);
    }

    /**
     * Generate a random nonce
     *
     * @access private
     * @return string
     */
    private function _generateNonce()
    {
        $bytes = ceil(256 / 8);
        $nonce = '';

        for ($i = 0; $i < $bytes; $i++) {
            $nonce .= chr(mt_rand(0, 255));
        }

        return substr(hash('sha512', $nonce), 0, 12);
    }

    /**
     * Generate the signature of the API request
     *
     * @param string $method The request method
     * @param string $nonce The nonce
     * @param int $timestamp The timestamp
     * @param string $requestUrl The request url
     * @access private
     * @return string
     */
    protected function _generateSignature($method, $nonce, $timestamp, $requestUrl)
    {
        $signatureToSign = sprintf('%s&%s&%s&%s&%s', $method, $this->_clientToken, $nonce, $timestamp, $requestUrl);
        return hash_hmac('sha1', $signatureToSign, $this->_clientSecret);
    }
}