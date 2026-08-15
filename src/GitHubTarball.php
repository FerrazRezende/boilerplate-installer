<?php

namespace Boilerplate\Installer;

use Symfony\Component\Filesystem\Filesystem;

class GitHubTarball implements ProjectDownloader
{
    public function __construct(
        private HttpDownloader $http,
        private GitHubTokenResolver $tokenResolver,
    ) {}

    public function download(string $repo, string $ref, string $destinationDir): void
    {
        $token = $this->tokenResolver->resolve();

        if ($token === null) {
            throw new RepositoryAccessException(
                "Sem credencial do GitHub para acessar {$repo}. Rode `gh auth login` ou exporte GITHUB_TOKEN e tente novamente.",
            );
        }

        try {
            $bytes = $this->http->get(
                "https://api.github.com/repos/{$repo}/tarball/{$ref}",
                [
                    'Authorization' => "Bearer {$token}",
                    'User-Agent' => 'boilerplate-installer',
                ],
            );
        } catch (HttpRequestException $exception) {
            if ($exception->statusCode === 404) {
                throw new RepositoryAccessException(
                    "Não consegui acessar {$repo}@{$ref}. Verifique se o token tem acesso a este repositório privado.",
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

        $entries = array_values(array_diff(scandir($extractedDir), ['.', '..']));
        $wrappingDir = $extractedDir.'/'.$entries[0];

        $filesystem->mkdir($destinationDir);
        $filesystem->mirror($wrappingDir, $destinationDir);
        $filesystem->remove($tmpDir);
    }
}
