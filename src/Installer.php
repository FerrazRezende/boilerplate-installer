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

    /**
     * Optional features ship enabled in the boilerplate and are stripped here
     * when they were not asked for, which keeps them developed and tested like
     * any other code instead of living as templates in this package.
     *
     * Order matters: strip first, flatten last. That way --mvc never needs to
     * know which optional features exist, and they never need to know about it.
     */
    public function install(
        string $targetPath,
        string $ref = 'main',
        bool $force = false,
        bool $withMvc = false,
        bool $withObs = false,
        bool $withAi = false,
    ): void {
        $this->assertDestinationIsUsable($targetPath, $force);

        $tmpDir = sys_get_temp_dir().'/boilerplate-new-'.uniqid();
        $this->downloader->download($this->repo, $ref, $tmpDir);

        try {
            $this->filesystem->copy($tmpDir.'/.env.example', $tmpDir.'/.env');
            $this->runner->run(['composer', 'install'], $tmpDir);

            if (! $withAi) {
                $this->runner->run(['php', 'scripts/remove-feature.php', 'Ai'], $tmpDir);
                // Dropping the package also regenerates the autoloader.
                $this->runner->run(['composer', 'remove', 'laravel/ai', '--no-interaction'], $tmpDir);
            }

            if (! $withObs) {
                $this->runner->run(['php', 'scripts/remove-feature.php', 'Observability'], $tmpDir);
                $this->runner->run(['composer', 'dump-autoload'], $tmpDir);
            }

            if ($withMvc) {
                // The flattener lives in the boilerplate, next to the structure
                // it rewrites, and deletes itself once done. It runs after
                // composer install because it finishes with a Pint pass.
                $this->runner->run(['php', 'scripts/to-mvc.php'], $tmpDir);
                $this->runner->run(['composer', 'remove', 'nwidart/laravel-modules', '--no-interaction'], $tmpDir);
            }

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
