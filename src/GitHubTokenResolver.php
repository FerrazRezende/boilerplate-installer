<?php

namespace Boilerplate\Installer;

class GitHubTokenResolver
{
    /**
     * @param  array<string, string>  $env
     * @param  \Closure(): (string|null)  $ghAuthToken
     */
    public function __construct(
        private array $env,
        private \Closure $ghAuthToken,
        private string $composerAuthJsonPath,
    ) {}

    public function resolve(): ?string
    {
        if (! empty($this->env['GITHUB_TOKEN'])) {
            return $this->env['GITHUB_TOKEN'];
        }

        $ghToken = ($this->ghAuthToken)();
        if (! empty($ghToken)) {
            return $ghToken;
        }

        return $this->fromComposerAuthJson();
    }

    private function fromComposerAuthJson(): ?string
    {
        if (! is_file($this->composerAuthJsonPath)) {
            return null;
        }

        $auth = json_decode(file_get_contents($this->composerAuthJsonPath), true);

        return $auth['github-oauth']['github.com'] ?? null;
    }
}
