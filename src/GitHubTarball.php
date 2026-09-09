<?php

namespace Boilerplate\Installer;

use Symfony\Component\Filesystem\Filesystem;

class GitHubTarball implements ProjectDownloader
{
    public function __construct(private HttpDownloader $http) {}

    public function download(string $repo, string $ref, string $destinationDir): void
    {
        try {
            $bytes = $this->http->get(
                "https://api.github.com/repos/{$repo}/tarball/{$ref}",
                ['User-Agent' => 'boilerplate-installer'],
            );
        } catch (HttpRequestException $exception) {
            if ($exception->statusCode === 404) {
                throw new RepositoryAccessException(
                    "Não encontrei {$repo}@{$ref}. Confira o nome da branch, tag ou commit passado em --ref.",
                );
            }

            if ($exception->statusCode === 403) {
                throw new RepositoryAccessException(
                    'A API do GitHub recusou a requisição, provavelmente por limite de uso '
                    .'(60 por hora por IP em chamadas anônimas). Tente de novo mais tarde.',
                );
            }

            throw $exception;
        }

        $this->extract($bytes, $destinationDir);
    }

    private function extract(string $tarballBytes, string $destinationDir): void
    {
        $filesystem = new Filesystem();
        $tmpDir = sys_get_temp_dir().'/boilerplate-tarball-'.uniqid();
        $filesystem->mkdir($tmpDir);

        $tarGzPath = $tmpDir.'/archive.tar.gz';
        file_put_contents($tarGzPath, $tarballBytes);

        $extractedDir = $tmpDir.'/extracted';
        (new \PharData($tarGzPath))->extractTo($extractedDir);

        // GitHub wraps the tree in a single <owner>-<repo>-<sha> directory.
        $entries = array_values(array_diff(scandir($extractedDir), ['.', '..']));
        $wrappingDir = $extractedDir.'/'.$entries[0];

        $filesystem->mkdir($destinationDir);
        $filesystem->mirror($wrappingDir, $destinationDir);
        $filesystem->remove($tmpDir);
    }
}
