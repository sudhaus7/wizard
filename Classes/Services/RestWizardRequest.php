<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 project.
 *
 * @author Frank Berger <fberger@sudhaus7.de>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace SUDHAUS7\Sudhaus7Wizard\Services;

use TYPO3\CMS\Core\Http\RequestFactory;

final class RestWizardRequest
{
    protected string $API_HOST = '';
    protected string $API_URL = '';
    protected string $API_SHARED_SECRET = '';

    private RequestFactory $requestFactory;
    // We need the RequestFactory for creating and sending a request,
    // so we inject it into the class using constructor injection.
    public function __construct(
        RequestFactory $requestFactory,
    ) {
        $this->requestFactory = $requestFactory;
    }

    /**
     * @return array<array-key, mixed>
     *
     * @TODO add paging
     */
    public function request(string $endpoint): array
    {
        $additionalOptions = [
            'headers' => ['Cache-Control' => 'no-cache'],
            'allow_redirects' => false,
        ];

        if (!empty($this->getAPISHAREDSECRET())) {
            $password = \password_hash($this->getAPISHAREDSECRET(), \PASSWORD_DEFAULT);
            $additionalOptions['headers']['X-Authorization'] = \base64_encode($password);
        }

        // $additionalOptions['headers']['X-Authorization']=\base64_encode( 'bla');
        // Get a PSR-7-compliant response object
        $response = $this->requestFactory->request(
            $this->getAPIHOST() . $this->getAPIURL() . trim($endpoint, '/'),
            'GET',
            $additionalOptions
        );

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException(
                'Returned status code is ' . $response->getStatusCode()
            );
        }

        if ($response->getHeaderLine('Content-Type') !== 'application/json') {
            throw new \RuntimeException(
                'The request did not return JSON data'
            );
        }
        $body = $response->getBody()->getContents();
        $decoded = json_decode($body, true);
        if (\is_array($decoded)) {
            return $decoded;
        }
        throw new \RuntimeException('No information available');
        // Get the content as a string on a successful request
    }

    /**
     * @param array<array-key, mixed> $body
     * @return array<array-key, mixed>
     */
    public function post(string $endpoint, array $body): array
    {
        $additionalOptions = [
            'headers' => ['Cache-Control' => 'no-cache'],
            'allow_redirects' => false,
            'form_params' => $body,
        ];

        if (!empty($this->getAPISHAREDSECRET())) {
            $password = \password_hash($this->getAPISHAREDSECRET(), \PASSWORD_DEFAULT);
            $additionalOptions['headers']['X-Authorization'] = \base64_encode($password);
        }

        // Get a PSR-7-compliant response object
        $response = $this->requestFactory->request(
            $this->getAPIHOST() . $this->getAPIURL() . trim($endpoint, '/'),
            'POST',
            $additionalOptions
        );

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException(
                'Returned status code is ' . $response->getStatusCode()
            );
        }

        if ($response->getHeaderLine('Content-Type') !== 'application/json') {
            throw new \RuntimeException(
                'The request did not return JSON data'
            );
        }

        $body = $response->getBody()->getContents();
        $decoded = json_decode($body, true);
        if (\is_array($decoded)) {
            return $decoded;
        }
        throw new \RuntimeException('No information available');
    }

    public function getAPIHOST(): string
    {
        return $this->API_HOST;
    }

    public function setAPIHOST(string $API_HOST): void
    {
        $this->API_HOST = trim($API_HOST, '/') . '/';
    }

    public function getAPIURL(): string
    {
        return $this->API_URL;
    }

    public function setAPIURL(string $API_URL): void
    {
        $this->API_URL = trim($API_URL, '/') . '/';
    }

    public function getAPISHAREDSECRET(): string
    {
        return $this->API_SHARED_SECRET;
    }

    public function setAPISHAREDSECRET(string $API_SHARED_SECRET): void
    {
        $this->API_SHARED_SECRET = $API_SHARED_SECRET;
    }
}
