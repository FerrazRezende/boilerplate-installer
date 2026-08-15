<?php

namespace Boilerplate\Installer\Tests;

use Boilerplate\Installer\GitHubTokenResolver;
use PHPUnit\Framework\TestCase;

class GitHubTokenResolverTest extends TestCase
{
    public function test_it_prefers_the_github_token_environment_variable(): void
    {
        $resolver = new GitHubTokenResolver(
            env: ['GITHUB_TOKEN' => 'env-token'],
            ghAuthToken: fn () => 'gh-token',
            composerAuthJsonPath: '/nonexistent/auth.json',
        );

        $this->assertSame('env-token', $resolver->resolve());
    }

    public function test_it_falls_back_to_gh_auth_token_when_env_is_missing(): void
    {
        $resolver = new GitHubTokenResolver(
            env: [],
            ghAuthToken: fn () => 'gh-token',
            composerAuthJsonPath: '/nonexistent/auth.json',
        );

        $this->assertSame('gh-token', $resolver->resolve());
    }

    public function test_it_falls_back_to_composer_auth_json_when_gh_is_unavailable(): void
    {
        $authJsonPath = sys_get_temp_dir().'/boilerplate-auth-'.uniqid().'.json';
        file_put_contents($authJsonPath, json_encode([
            'github-oauth' => ['github.com' => 'composer-token'],
        ]));

        $resolver = new GitHubTokenResolver(
            env: [],
            ghAuthToken: fn () => null,
            composerAuthJsonPath: $authJsonPath,
        );

        $this->assertSame('composer-token', $resolver->resolve());

        unlink($authJsonPath);
    }

    public function test_it_returns_null_when_no_credentials_are_available(): void
    {
        $resolver = new GitHubTokenResolver(
            env: [],
            ghAuthToken: fn () => null,
            composerAuthJsonPath: '/nonexistent/auth.json',
        );

        $this->assertNull($resolver->resolve());
    }
}
