<?php

namespace Boilerplate\Installer;

use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class SymfonyProcessRunner implements ProcessRunner
{
    public function __construct(private ?OutputInterface $output = null) {}

    public function run(array $command, string $cwd): void
    {
        $process = new Process($command, $cwd, timeout: null);

        $process->run(function (string $type, string $buffer): void {
            $this->output?->write($buffer);
        });

        if (! $process->isSuccessful()) {
            throw new ProcessFailedException(
                'Comando falhou: '.implode(' ', $command)."\n".$process->getErrorOutput(),
            );
        }
    }
}
