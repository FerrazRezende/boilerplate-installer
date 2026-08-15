<?php

namespace Boilerplate\Installer;

class HttpRequestException extends \RuntimeException
{
    public function __construct(string $message, public readonly int $statusCode)
    {
        parent::__construct($message);
    }
}
