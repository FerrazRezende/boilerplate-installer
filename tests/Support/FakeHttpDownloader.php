<?php

namespace Boilerplate\Installer\Tests\Support;

use Boilerplate\Installer\HttpDownloader;
use Boilerplate\Installer\HttpRequestException;

class FakeHttpDownloader implements HttpDownloader
{
    public ?string $requestedUrl = null;

    /** @var array<string, string> */
    public array $requestedHeaders = [];

    public function __construct(
        private ?string $responseBody = null,
        private ?int $failWithStatus = null,
    ) {}

    public function get(string $url, array $headers): string
    {
        $this->requestedUrl = $url;
        $this->requestedHeaders = $headers;

        if ($this->failWithStatus !== null) {
            throw new HttpRequestException("HTTP {$this->failWithStatus}", $this->failWithStatus);
        }

        return $this->responseBody;
    }
}
