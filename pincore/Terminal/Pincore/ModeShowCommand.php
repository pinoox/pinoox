<?php

namespace Pinoox\Terminal\Pincore;

use Pinoox\Component\Package\AppEnv\AppEnvBridge;
use Pinoox\Component\Runtime\RuntimeMode;
use Pinoox\Component\Terminal;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\Mode;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'mode:show',
    description: 'Show runtime mode profile for the project and installed apps',
)]

class ModeShowCommand extends Terminal
{
    protected function configure(): void
    {
        $this
            ->setHelp(
                <<<'HELP'
Displays global runtime mode from pinoox.config and per-app overrides from app.php → runtime
or apps/{package}/.env (THEME, MODE, DEBUG, …).

Examples:

  php pinoox mode:show
  php pinoox mode:show com_my_shop

HELP
            )
            ->addArgument('package', InputArgument::OPTIONAL, 'Show profile for one app package');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $package = $input->getArgument('package');
        $global = RuntimeMode::readGlobal();

        $output->writeln('<info>Global runtime</info>');
        $output->writeln(sprintf(
            '  mode: <comment>%s</comment>  debug: <comment>%s</comment>  database: <comment>%s</comment>',
            $global['mode'],
            $global['debug'] ? 'true' : 'false',
            Mode::databaseConnection(),
        ));
        $output->writeln('  supported: ' . implode(', ', RuntimeMode::supported()));
        $output->writeln('');

        if (is_string($package) && $package !== '') {
            if (!AppEngine::exists($package)) {
                $output->writeln("<error>App '{$package}' not found.</error>");

                return Command::FAILURE;
            }

            $this->renderProfile($output, $package);

            return Command::SUCCESS;
        }

        $rows = [];
        foreach (AppEngine::all() as $name => $manager) {
            if (!$manager->exists()) {
                continue;
            }

            $profile = Mode::profile($name);
            $runtime = AppEngine::config($name)->get('runtime');
            $override = is_array($runtime) && ($runtime['mode'] ?? null) !== null
                ? (string) $runtime['mode']
                : '—';

            $envKeys = array_keys(AppEnvBridge::effective($name));
            $envHint = $envKeys !== [] ? implode(',', $envKeys) : '—';

            $rows[] = [
                $name,
                $profile['mode'],
                $override,
                $profile['debug'] ? 'yes' : 'no',
                $profile['database'],
                $profile['cache_enabled'] ? 'on' : 'off',
                $envHint,
            ];
        }

        if ($rows === []) {
            $output->writeln('<comment>No apps installed.</comment>');

            return Command::SUCCESS;
        }

        $table = new Table($output);
        $table
            ->setHeaders(['Package', 'Mode', 'Override', 'Debug', 'DB profile', 'Cache', 'App env'])
            ->setRows($rows);
        $table->render();

        return Command::SUCCESS;
    }

    private function renderProfile(OutputInterface $output, string $package): void
    {
        $profile = Mode::profile($package);

        $output->writeln("<info>App: {$package}</info>");

        $table = new Table($output);
        $table->setRows([
            ['Mode', $profile['mode']],
            ['Debug', $profile['debug'] ? 'true' : 'false'],
            ['Production', $profile['production'] ? 'true' : 'false'],
            ['Database profile', $profile['database']],
            ['Cache mode', $profile['cache_mode']],
            ['Cache enabled (default)', $profile['cache_enabled'] ? 'true' : 'false'],
        ]);
        $table->render();
    }
}

