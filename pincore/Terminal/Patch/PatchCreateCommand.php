<?php

namespace Pinoox\Terminal\Patch;

use Pinoox\Component\Helpers\Str;
use Pinoox\Component\Terminal;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\StubGenerator;
use Pinoox\Support\SystemConfig;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'patch:create',
    description: 'Create a new app patch.',
)]
class PatchCreateCommand extends Terminal
{
    private string $package;
    private string $patch;

    protected function configure(): void
    {
        $this
            ->addArgument('patch', InputArgument::REQUIRED, 'The name of the patch')
            ->addArgument('package', InputArgument::OPTIONAL, 'The package to create the patch in', $this->getDefaultPackage());
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $this->package = (string)$input->getArgument('package');
        $this->patch = (string)$input->getArgument('patch');

        $isCreated = StubGenerator::generate('patch.create.stub', $this->getExportPath(), [
            'copyright' => StubGenerator::get('copyright.stub'),
            'namespace' => $this->getNamespace(),
            'package' => $this->package,
        ]);

        if (!$isCreated) {
            $this->error('Failed to generate patch class.');

            return Command::FAILURE;
        }

        $this->success('Patch [' . $this->getPatchFileName() . '] created successfully');
        $this->info('Location: ' . $this->getPatchPath());

        return Command::SUCCESS;
    }

    private function getExportPath(): string
    {
        $path = $this->getPatchPath();

        if (!is_dir($path)) {
            mkdir($path, 0755, true);
        }

        return $path . '/' . $this->getPatchFileName() . '.php';
    }

    private function getPatchPath(): string
    {
        if ($this->package === 'pincore') {
            return SystemConfig::path('system_patches');
        }

        return AppEngine::path($this->package) . '/' . trim(SystemConfig::rawPath('app_patches', 'database/patches'), '/\\');
    }

    private function getPatchFileName(): string
    {
        $name = Str::toUnderScore(Str::toCamelCase($this->patch));

        return date('Y_m_d_His') . '_' . $name;
    }

    private function getNamespace(): string
    {
        return $this->package === 'pincore'
            ? 'Pinoox\\Database\\patches'
            : 'App\\' . $this->package . '\\database\\patches';
    }
}
