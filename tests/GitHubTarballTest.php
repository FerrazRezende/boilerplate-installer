<?php

namespace Boilerplate\Installer\Tests;

use Boilerplate\Installer\GitHubTarball;
use Boilerplate\Installer\GitHubTokenResolver;
use Boilerplate\Installer\RepositoryAccessException;
use Boilerplate\Installer\Tests\Support\FakeHttpDownloader;
use PHPUnit\Framework\TestCase;

class GitHubTarballTest extends TestCase
{
    private string $workDir;

    private string $destinationDir;

    protected function setUp(): void
    {
        $this->workDir = sys_get_temp_dir().'/boilerplate-tarball-test-'.uniqid();
        $this->destinationDir = $this->workDir.'/destination';
        mkdir($this->destinationDir, recursive: true);
    }

    protected function tearDown(): void
    {
        (new \Symfony\Component\Filesystem\Filesystem())->remove($this->workDir);
    }

    private function resolverWithToken(string $token): GitHubTokenResolver
    {
        return new GitHubTokenResolver(
            env: ['GITHUB_TOKEN' => $token],
            ghAuthToken: fn () => null,
            composerAuthJsonPath: '/nonexistent/auth.json',
        );
    }

    private function resolverWithoutToken(): GitHubTokenResolver
    {
        return new GitHubTokenResolver(
            env: [],
            ghAuthToken: fn () => null,
            composerAuthJsonPath: '/nonexistent/auth.json',
        );
    }

    /** Builds a tar.gz whose contents mimic a GitHub codeload tarball (single wrapping directory). */
    private function buildFixtureTarball(): string
    {
        $sourceDir = $this->workDir.'/fixture-source/FerrazRezende-laravel-boilerplate-abc123';
        mkdir($sourceDir, recursive: true);
        file_put_contents($sourceDir.'/composer.json', '{"name": "boilerplate/boilerplate"}');
        mkdir($sourceDir.'/app');
        file_put_contents($sourceDir.'/app/Kernel.php', '<?php');

        $tarPath = $this->workDir.'/fixture.tar';
        $phar = new \PharData($tarPath);
        $phar->buildFromDirectory($this->workDir.'/fixture-source');
        $phar->compress(\Phar::GZ);

        return file_get_contents($tarPath.'.gz');
    }

    public function test_it_downloads_and_extracts_stripping_the_wrapping_directory(): void
    {
        $tarballBytes = $this->buildFixtureTarball();
        $http = new FakeHttpDownloader(responseBody: $tarballBytes);

        (new GitHubTarball($http, $this->resolverWithToken('secret-token')))
            ->download('FerrazRezende/laravel-boilerplate', 'main', $this->destinationDir);

        $this->assertFileExists($this->destinationDir.'/composer.json');
        $this->assertFileExists($this->destinationDir.'/app/Kernel.php');
        $this->assertSame(
            '{"name": "boilerplate/boilerplate"}',
            file_get_contents($this->destinationDir.'/composer.json'),
        );
    }

    public function test_it_sends_the_token_as_a_bearer_header(): void
    {
        $http = new FakeHttpDownloader(responseBody: $this->buildFixtureTarball());

        (new GitHubTarball($http, $this->resolverWithToken('secret-token')))
            ->download('FerrazRezende/laravel-boilerplate', 'main', $this->destinationDir);

        $this->assertSame('Bearer secret-token', $http->requestedHeaders['Authorization']);
        $this->assertSame(
            'https://api.github.com/repos/FerrazRezende/laravel-boilerplate/tarball/main',
            $http->requestedUrl,
        );
    }

    public function test_it_raises_a_clear_error_when_no_credentials_are_available(): void
    {
        $http = new FakeHttpDownloader();

        $this->expectException(RepositoryAccessException::class);
        $this->expectExceptionMessageMatches('/GITHUB_TOKEN|gh auth login/');

        (new GitHubTarball($http, $this->resolverWithoutToken()))
            ->download('FerrazRezende/laravel-boilerplate', 'main', $this->destinationDir);
    }

    public function test_it_maps_a_404_to_a_repository_access_error(): void
    {
        $http = new FakeHttpDownloader(failWithStatus: 404);

        $this->expectException(RepositoryAccessException::class);
        $this->expectExceptionMessageMatches('/FerrazRezende\/laravel-boilerplate/');

        (new GitHubTarball($http, $this->resolverWithToken('secret-token')))
            ->download('FerrazRezende/laravel-boilerplate', 'main', $this->destinationDir);
    }
}
