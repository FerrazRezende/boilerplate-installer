<?php

namespace Boilerplate\Installer;

use Symfony\Component\Filesystem\Filesystem;

class Installer
{
    private Filesystem $filesystem;

    public function __construct(
        private ProjectDownloader $downloader,
        private ProcessRunner $runner,
        private string $repo = 'FerrazRezende/laravel-boilerplate',
        ?Filesystem $filesystem = null,
    ) {
        $this->filesystem = $filesystem ?? new Filesystem();
    }

    public function install(string $targetPath, string $ref = 'main', bool $force = false): void
    {
        $this->assertDestinationIsUsable($targetPath, $force);

        $tmpDir = sys_get_temp_dir().'/boilerplate-new-'.uniqid();
        $this->downloader->download($this->repo, $ref, $tmpDir);

        try {
            $this->filesystem->copy($tmpDir.'/.env.example', $tmpDir.'/.env');
            $this->runner->run(['composer', 'install'], $tmpDir);
            $this->runner->run(['php', 'artisan', 'key:generate'], $tmpDir);
            $this->runner->run(['npm', 'install'], $tmpDir);
        } catch (\Throwable $exception) {
            $this->moveIntoPlace($tmpDir, $targetPath, $force);

            throw new PartialInstallException($targetPath, $exception);
        }

        $this->moveIntoPlace($tmpDir, $targetPath, $force);
    }

    private function assertDestinationIsUsable(string $targetPath, bool $force): void
    {
        if (! $this->filesystem->exists($targetPath)) {
            return;
        }

        $isEmpty = is_dir($targetPath) && count(scandir($targetPath)) === 2;

        if ($isEmpty || $force) {
            return;
        }

        throw new DestinationExistsException(
            "O diretório {$targetPath} já existe e não está vazio. Use --force para sobrescrever.",
        );
    }

    private function moveIntoPlace(string $tmpDir, string $targetPath, bool $force): void
    {
        if ($force && $this->filesystem->exists($targetPath)) {
            $this->filesystem->remove($targetPath);
        }

        $this->filesystem->mkdir(dirname($targetPath));
        $this->filesystem->rename($tmpDir, $targetPath, overwrite: true);
    }
}
