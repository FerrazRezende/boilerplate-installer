# boilerplate-installer

CLI para criar novos projetos a partir do [`FerrazRezende/laravel-boilerplate`](https://github.com/FerrazRezende/laravel-boilerplate).

Ambos os repositórios são públicos: não é preciso conta no GitHub, chave SSH nem token para instalar ou usar.

## Instalação (uma vez por máquina)

```bash
composer global require ferrazrezende/boilerplate-installer
```

Garanta que `~/.composer/vendor/bin` (ou `~/.config/composer/vendor/bin`) esteja no `PATH`.

## Uso

```bash
boilerplate new meu-app
```

Opções:

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
