<?php

namespace Boilerplate\Installer\Tests;

use Boilerplate\Installer\GitHubTarball;
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
        $http = new FakeHttpDownloader(responseBody: $this->buildFixtureTarball());

        (new GitHubTarball($http))
            ->download('FerrazRezende/laravel-boilerplate', 'main', $this->destinationDir);

        $this->assertFileExists($this->destinationDir.'/composer.json');
        $this->assertFileExists($this->destinationDir.'/app/Kernel.php');
        $this->assertSame(
            '{"name": "boilerplate/boilerplate"}',
            file_get_contents($this->destinationDir.'/composer.json'),
        );
    }

    public function test_it_asks_github_anonymously(): void
    {
        $http = new FakeHttpDownloader(responseBody: $this->buildFixtureTarball());

        (new GitHubTarball($http))
            ->download('FerrazRezende/laravel-boilerplate', 'main', $this->destinationDir);

        // The repositories are public. Sending credentials would put the
        // installer back to needing a GitHub account to run.
        $this->assertArrayNotHasKey('Authorization', $http->requestedHeaders);
        $this->assertSame(
            'https://api.github.com/repos/FerrazRezende/laravel-boilerplate/tarball/main',
            $http->requestedUrl,
        );
    }

    public function test_it_explains_a_404_as_a_bad_ref(): void
    {
        $http = new FakeHttpDownloader(failWithStatus: 404);

        $this->expectException(RepositoryAccessException::class);
        $this->expectExceptionMessageMatches('/--ref/');

        (new GitHubTarball($http))
            ->download('FerrazRezende/laravel-boilerplate', 'nope', $this->destinationDir);
    }

    public function test_it_explains_a_403_as_the_anonymous_rate_limit(): void
    {
        $http = new FakeHttpDownloader(failWithStatus: 403);

        $this->expectException(RepositoryAccessException::class);
        $this->expectExceptionMessageMatches('/limite de uso/');

        (new GitHubTarball($http))
            ->download('FerrazRezende/laravel-boilerplate', 'main', $this->destinationDir);
    }
}
