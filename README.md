# boilerplate-installer

CLI para criar novos projetos a partir do [`FerrazRezende/laravel-boilerplate`](https://github.com/FerrazRezende/laravel-boilerplate).

Ambos os repositórios são públicos: não é preciso conta no GitHub, chave SSH nem token para instalar ou usar.

## Instalação (uma vez por máquina)

O pacote ainda não está no Packagist, então registre o repositório antes de instalar:

```bash
composer global config repositories.boilerplate vcs https://github.com/FerrazRezende/boilerplate-installer.git
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

Depois de criar o projeto:

```bash
cd meu-app
make setup
```

## Limite de uso da API do GitHub

O download é anônimo e a API do GitHub permite 60 chamadas por hora por IP. Isso é
folgado para uso normal, mas pode apertar em CI compartilhado. Se bater o limite,
o instalador diz isso explicitamente em vez de falhar de forma obscura.

## Desenvolvimento

```bash
composer install
vendor/bin/phpunit
```
