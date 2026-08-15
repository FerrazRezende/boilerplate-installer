<?php

namespace Boilerplate\Installer;

interface HttpDownloader
{
    /**
     * @param  array<string, string>  $headers
     *
     * @throws HttpRequestException
     */
    public function get(string $url, array $headers): string;
}
