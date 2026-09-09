<?php

namespace Boilerplate\Installer\Console;

use Boilerplate\Installer\AiRecipe;
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

class NewCommand extends Command
{
    protected function configure(): void
    {
        $this->setName('new')
            ->setDescription('Cria um novo projeto a partir do laravel-boilerplate')
            ->addArgument('path', InputArgument::REQUIRED, 'Diretório do novo projeto')
            ->addOption('ai', null, InputOption::VALUE_NONE, 'Instala e configura o Laravel AI SDK (laravel/ai)')
            ->addOption('ref', null, InputOption::VALUE_REQUIRED, 'Branch, tag ou commit do boilerplate', 'main')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Sobrescreve o diretório de destino se já existir');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $targetPath = rtrim((string) $input->getArgument('path'), '/');

        $runner = new SymfonyProcessRunner($output);
        $installer = new Installer(
            downloader: new GitHubTarball(new GuzzleHttpDownloader(new Client())),
            runner: $runner,
            aiRecipe: new AiRecipe($runner),
        );

        $io->title('Criando projeto Laravel Boilerplate');

        try {
            $installer->install(
                targetPath: $targetPath,
                withAi: (bool) $input->getOption('ai'),
                ref: (string) $input->getOption('ref'),
                force: (bool) $input->getOption('force'),
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
}
