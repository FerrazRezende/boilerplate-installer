<?php

namespace Boilerplate\Installer;

use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\RequestException;

class GuzzleHttpDownloader implements HttpDownloader
{
    public function __construct(private ClientInterface $client) {}

    public function get(string $url, array $headers): string
    {
        try {
            $response = $this->client->request('GET', $url, [
                'headers' => $headers,
                'allow_redirects' => true,
            ]);
        } catch (RequestException $exception) {
            $status = $exception->getResponse()?->getStatusCode() ?? 0;

            throw new HttpRequestException($exception->getMessage(), $status);
        }

        return (string) $response->getBody();
    }
}
