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
boilerplate new meu-app                # abre o seletor de opções
boilerplate new meu-app --obs --ai     # ou passe as flags direto
boilerplate new meu-app --obs --mvc    # as opções combinam
```

Rodando sem nenhuma flag de feature num terminal, o instalador abre um seletor
para você marcar o que quer. Flag passada explicitamente vence e pula o
seletor, então script e CI se comportam exatamente como escrito;
`--no-interaction` assume nenhuma feature.

Opções:

- `--obs` — inclui o módulo de observabilidade: uma trait `TracksProgress` que
  os jobs usam para reportar quanto falta, o progresso guardado no Redis e
  transmitido por Reverb, e a tela `/system/jobs` com as barras ao vivo. O
  Horizon continua sendo o lugar de investigar falhas; esta tela é a visão ao
  vivo, e linka para ele.
- `--ai` — inclui o assistente: o Laravel AI SDK configurado, com um balão de
  chat no app e um chat na landing page. Preencha uma key de provider no `.env`
  (`ANTHROPIC_API_KEY`, `OPENAI_API_KEY`, …) e ele responde; sem key, os chats
  informam que não estão configurados em vez de quebrar. O chat público é
  limitado por IP, já que qualquer visitante o alcança.

- `--mvc` — converte o projeto para o layout MVC padrão do Laravel: tudo em
  `app/` com namespace `App\`, rotas em `routes/`, traduções em `lang/` e as
  páginas Vue em `resources/js/Pages`, sem `nwidart/laravel-modules`. A
  conversão é feita pelo `scripts/to-mvc.php` do próprio boilerplate, que se
  remove ao terminar.
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
