<?php

namespace Boilerplate\Installer;

interface ProcessRunner
{
    /**
     * @param  array<int, string>  $command
     *
     * @throws ProcessFailedException
     */
    public function run(array $command, string $cwd): void;
}
