<?php

namespace Boilerplate\Installer;

class PartialInstallException extends \RuntimeException
{
    public function __construct(public readonly string $targetPath, \Throwable $previous)
    {
        parent::__construct(
            "O projeto foi criado em {$targetPath}, mas uma etapa falhou: {$previous->getMessage()}",
            previous: $previous,
        );
    }
}
