<?php

namespace Boilerplate\Installer\Tests\Support;

use Boilerplate\Installer\ProcessRunner;

class FakeProcessRunner implements ProcessRunner
{
    /** @var array<int, array{command: array<int, string>, cwd: string}> */
    public array $calls = [];

    /** @var array<int, string> commands (joined) that should throw when run */
    public array $failing = [];

    public function run(array $command, string $cwd): void
    {
        $this->calls[] = ['command' => $command, 'cwd' => $cwd];

        if (in_array(implode(' ', $command), $this->failing, true)) {
            throw new \Boilerplate\Installer\ProcessFailedException(
                'Command failed: '.implode(' ', $command)
            );
        }
    }
}
