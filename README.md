# boilerplate-installer

CLI para criar novos projetos a partir do [`FerrazRezende/laravel-boilerplate`](https://github.com/FerrazRezende/laravel-boilerplate) (repositório privado).

## Instalação (uma vez por máquina)

```bash
composer global config repositories.boilerplate vcs git@github.com:FerrazRezende/boilerplate-installer.git
composer global require ferrazrezende/boilerplate-installer
```

Garanta que `~/.composer/vendor/bin` (ou `~/.config/composer/vendor/bin`) esteja no `PATH`.

## Uso

```bash
boilerplate new meu-app
boilerplate new meu-app --ai      # inclui o Laravel AI SDK (laravel/ai) já configurado
```

Opções:

- `--ai` — roda `composer require laravel/ai`, publica config/migrations e adiciona as chaves de provider (vazias) ao `.env`/`.env.example`.
- `--ref=<branch|tag|sha>` — versão do boilerplate a baixar (padrão `main`).
- `--force` — sobrescreve o diretório de destino se ele já existir e não estiver vazio.

## Autenticação com o GitHub

O boilerplate é privado, então o instalador precisa de um token com acesso ao repositório. Ele tenta, nesta ordem:

1. variável de ambiente `GITHUB_TOKEN`;
2. `gh auth token` (se o [GitHub CLI](https://cli.github.com) estiver autenticado);
3. `github-oauth` do `auth.json` do Composer.

## Desenvolvimento

```bash
composer install
vendor/bin/phpunit
```
