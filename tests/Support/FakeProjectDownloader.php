<?php

namespace Boilerplate\Installer\Tests\Support;

use Boilerplate\Installer\ProjectDownloader;

class FakeProjectDownloader implements ProjectDownloader
{
    public ?string $requestedRepo = null;

    public ?string $requestedRef = null;

    public function download(string $repo, string $ref, string $destinationDir): void
    {
        $this->requestedRepo = $repo;
        $this->requestedRef = $ref;

        mkdir($destinationDir, recursive: true);
        file_put_contents($destinationDir.'/composer.json', '{"name": "boilerplate/boilerplate"}');
        file_put_contents($destinationDir.'/.env.example', "APP_NAME=Boilerplate\nAPP_KEY=\n");
    }
}
