<?php

namespace Boilerplate\Installer\Tests;

use Boilerplate\Installer\DestinationExistsException;
use Boilerplate\Installer\Installer;
use Boilerplate\Installer\PartialInstallException;
use Boilerplate\Installer\Tests\Support\FakeProcessRunner;
use Boilerplate\Installer\Tests\Support\FakeProjectDownloader;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;

class InstallerTest extends TestCase
{
    private string $workDir;

    private string $targetPath;

    protected function setUp(): void
    {
        $this->workDir = sys_get_temp_dir().'/boilerplate-installer-test-'.uniqid();
        $this->targetPath = $this->workDir.'/meu-app';
    }

    protected function tearDown(): void
    {
        (new Filesystem())->remove($this->workDir);
    }

    private function makeInstaller(FakeProcessRunner $runner, FakeProjectDownloader $downloader): Installer
    {
        return new Installer($downloader, $runner);
    }

    public function test_it_downloads_installs_dependencies_and_moves_the_project_to_the_target_path(): void
    {
        $runner = new FakeProcessRunner();
        $downloader = new FakeProjectDownloader();

        $this->makeInstaller($runner, $downloader)->install($this->targetPath);

        $this->assertFileExists($this->targetPath.'/composer.json');
        $this->assertFileExists($this->targetPath.'/.env');
        $this->assertSame('FerrazRezende/laravel-boilerplate', $downloader->requestedRepo);

        $commands = array_map(fn ($call) => implode(' ', $call['command']), $runner->calls);
        $this->assertContains('composer install', $commands);
        $this->assertContains('php artisan key:generate', $commands);
        $this->assertContains('npm install', $commands);
    }

    public function test_it_refuses_to_overwrite_a_non_empty_existing_directory_without_force(): void
    {
        mkdir($this->targetPath, recursive: true);
        file_put_contents($this->targetPath.'/existing-file.txt', 'keep me');

        $downloader = new FakeProjectDownloader();

        $this->expectException(DestinationExistsException::class);
        $this->expectExceptionMessageMatches('/--force/');

        try {
            $this->makeInstaller(new FakeProcessRunner(), $downloader)->install($this->targetPath);
        } finally {
            $this->assertNull($downloader->requestedRepo, 'download should not start when the destination is rejected');
        }
    }

    public function test_it_overwrites_an_existing_directory_when_forced(): void
    {
        mkdir($this->targetPath, recursive: true);
        file_put_contents($this->targetPath.'/old-file.txt', 'discard me');

        $this->makeInstaller(new FakeProcessRunner(), new FakeProjectDownloader())
            ->install($this->targetPath, force: true);

        $this->assertFileDoesNotExist($this->targetPath.'/old-file.txt');
        $this->assertFileExists($this->targetPath.'/composer.json');
    }

    public function test_it_still_places_the_project_when_a_post_download_step_fails(): void
    {
        $runner = new FakeProcessRunner();
        $runner->failing = ['composer install'];

        try {
            $this->makeInstaller($runner, new FakeProjectDownloader())->install($this->targetPath);
            $this->fail('Expected a PartialInstallException to be thrown.');
        } catch (PartialInstallException $exception) {
            $this->assertSame($this->targetPath, $exception->targetPath);
        }

        $this->assertFileExists($this->targetPath.'/composer.json');
    }
}
