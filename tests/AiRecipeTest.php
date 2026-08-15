<?php

namespace Boilerplate\Installer\Tests;

use Boilerplate\Installer\AiRecipe;
use Boilerplate\Installer\Tests\Support\FakeProcessRunner;
use PHPUnit\Framework\TestCase;

class AiRecipeTest extends TestCase
{
    private string $projectPath;

    protected function setUp(): void
    {
        $this->projectPath = sys_get_temp_dir().'/boilerplate-ai-recipe-'.uniqid();
        mkdir($this->projectPath, recursive: true);
        file_put_contents($this->projectPath.'/.env', "APP_NAME=Boilerplate\n");
        file_put_contents($this->projectPath.'/.env.example', "APP_NAME=Boilerplate\n");
    }

    protected function tearDown(): void
    {
        $this->removeDirectory($this->projectPath);
    }

    public function test_it_requires_the_laravel_ai_package(): void
    {
        $runner = new FakeProcessRunner();

        (new AiRecipe($runner))->apply($this->projectPath);

        $this->assertContains(
            ['command' => ['composer', 'require', 'laravel/ai'], 'cwd' => $this->projectPath],
            $runner->calls,
        );
    }

    public function test_it_publishes_the_ai_service_provider_config_and_migrations(): void
    {
        $runner = new FakeProcessRunner();

        (new AiRecipe($runner))->apply($this->projectPath);

        $this->assertContains(
            [
                'command' => ['php', 'artisan', 'vendor:publish', '--provider=Laravel\Ai\AiServiceProvider'],
                'cwd' => $this->projectPath,
            ],
            $runner->calls,
        );
    }

    public function test_it_appends_ai_provider_keys_to_env_and_env_example(): void
    {
        (new AiRecipe(new FakeProcessRunner()))->apply($this->projectPath);

        foreach (['.env', '.env.example'] as $file) {
            $contents = file_get_contents($this->projectPath.'/'.$file);
            $this->assertStringContainsString('APP_NAME=Boilerplate', $contents);
            $this->assertStringContainsString('ANTHROPIC_API_KEY=', $contents);
            $this->assertStringContainsString('OPENAI_API_KEY=', $contents);
        }
    }

    public function test_it_does_not_duplicate_the_ai_block_when_applied_twice(): void
    {
        $recipe = new AiRecipe(new FakeProcessRunner());

        $recipe->apply($this->projectPath);
        $recipe->apply($this->projectPath);

        $contents = file_get_contents($this->projectPath.'/.env');
        $this->assertSame(1, substr_count($contents, 'ANTHROPIC_API_KEY='));
    }

    private function removeDirectory(string $path): void
    {
        if (! is_dir($path)) {
            return;
        }

        foreach (scandir($path) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $full = $path.'/'.$item;
            is_dir($full) ? $this->removeDirectory($full) : unlink($full);
        }

        rmdir($path);
    }
}
