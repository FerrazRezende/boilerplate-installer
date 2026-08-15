<?php

namespace Boilerplate\Installer;

class AiRecipe
{
    private const ENV_MARKER = '# AI PROVIDERS (Laravel AI SDK)';

    private const ENV_KEYS = [
        'ANTHROPIC_API_KEY',
        'COHERE_API_KEY',
        'ELEVENLABS_API_KEY',
        'GEMINI_API_KEY',
        'JINA_API_KEY',
        'MISTRAL_API_KEY',
        'OLLAMA_API_KEY',
        'OPENAI_API_KEY',
        'VOYAGEAI_API_KEY',
        'XAI_API_KEY',
    ];

    public function __construct(private ProcessRunner $runner) {}

    public function apply(string $projectPath): void
    {
        $this->runner->run(['composer', 'require', 'laravel/ai'], $projectPath);
        $this->runner->run(
            ['php', 'artisan', 'vendor:publish', '--provider=Laravel\Ai\AiServiceProvider'],
            $projectPath,
        );

        $this->appendEnvBlock($projectPath.'/.env');
        $this->appendEnvBlock($projectPath.'/.env.example');
    }

    private function appendEnvBlock(string $envFile): void
    {
        $contents = file_exists($envFile) ? file_get_contents($envFile) : '';

        if (str_contains($contents, self::ENV_MARKER)) {
            return;
        }

        $block = implode("\n", [
            '',
            '# =============================================================================',
            self::ENV_MARKER,
            '# =============================================================================',
            ...array_map(fn (string $key) => "{$key}=", self::ENV_KEYS),
            '',
        ]);

        file_put_contents($envFile, rtrim($contents)."\n".$block);
    }
}
