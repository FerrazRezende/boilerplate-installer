<?php

namespace Boilerplate\Installer;

interface ProjectDownloader
{
    public function download(string $repo, string $ref, string $destinationDir): void;
}
