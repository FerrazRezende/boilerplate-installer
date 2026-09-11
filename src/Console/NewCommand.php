<?php

namespace Boilerplate\Installer\Console;

use Boilerplate\Installer\DestinationExistsException;
use Boilerplate\Installer\GitHubTarball;
use Boilerplate\Installer\GuzzleHttpDownloader;
use Boilerplate\Installer\Installer;
use Boilerplate\Installer\PartialInstallException;
use Boilerplate\Installer\ProcessFailedException;
use Boilerplate\Installer\RepositoryAccessException;
use Boilerplate\Installer\SymfonyProcessRunner;
use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function Laravel\Prompts\multiselect;

class NewCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('new')
            ->setDescription('Cria um novo projeto a partir do laravel-boilerplate')
            ->addArgument('path', InputArgument::REQUIRED, 'Diretório do novo projeto')
            ->addOption('mvc', null, InputOption::VALUE_NONE, 'Gera o projeto no layout MVC plano do Laravel, sem módulos')
            ->addOption('obs', null, InputOption::VALUE_NONE, 'Inclui o módulo de observabilidade: progresso de jobs ao vivo e a tela /system/jobs')
            ->addOption('ai', null, InputOption::VALUE_NONE, 'Inclui o assistente: Laravel AI SDK com chat na landing page e no app')
            ->addOption('ref', null, InputOption::VALUE_REQUIRED, 'Branch, tag ou commit do boilerplate', 'main')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Sobrescreve o diretório de destino se já existir');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $targetPath = rtrim((string) $input->getArgument('path'), '/');

        $features = $this->chooseFeatures($input);

        $runner = new SymfonyProcessRunner($output);
        $installer = new Installer(
            downloader: new GitHubTarball(new GuzzleHttpDownloader(new Client())),
            runner: $runner,
        );

        $io->title('Criando projeto Laravel Boilerplate');

        try {
            $installer->install(
                targetPath: $targetPath,
                ref: (string) $input->getOption('ref'),
                force: (bool) $input->getOption('force'),
                withMvc: in_array('mvc', $features, true),
                withObs: in_array('obs', $features, true),
                withAi: in_array('ai', $features, true),
            );
        } catch (DestinationExistsException|RepositoryAccessException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        } catch (PartialInstallException $exception) {
            $io->warning($exception->getMessage());
            $io->note("Para retomar: cd {$targetPath} && composer install && npm install");

            return Command::FAILURE;
        } catch (ProcessFailedException $exception) {
            $io->error($exception->getMessage());

            return Command::FAILURE;
        }

        $io->success("Projeto criado em {$targetPath}.");
        $io->writeln([
            '',
            'Próximos passos:',
            "  cd {$targetPath}",
            '  make setup',
        ]);

        return Command::SUCCESS;
    }

    /**
     * Which options this run should apply.
     *
     * A flag passed explicitly always wins and skips the prompt, so scripted
     * and CI runs behave exactly as written. The picker only appears when the
     * command was given no options at all and there is a terminal to draw in.
     *
     * @return array<int, string>
     */
    private function chooseFeatures(InputInterface $input): array
    {
        $chosen = array_keys(array_filter([
            'mvc' => (bool) $input->getOption('mvc'),
            'obs' => (bool) $input->getOption('obs'),
            'ai' => (bool) $input->getOption('ai'),
        ]));

        if ($chosen !== [] || ! $input->isInteractive() || ! stream_isatty(STDIN)) {
            return $chosen;
        }

        return multiselect(
            label: 'O que incluir neste projeto?',
            options: [
                'obs' => 'Observabilidade — progresso de jobs ao vivo e a tela /system/jobs',
                'ai' => 'Assistente — Laravel AI SDK, com chat na landing page e no app',
                'mvc' => 'Layout MVC plano — tudo em app/, sem módulos',
            ],
            hint: 'Espaço seleciona, Enter confirma. Nenhuma opção também é válido.',
        );
    }
}
