<?php

namespace Pinoox\Terminal\Patch;

use Pinoox\Component\Helpers\Str;
use Pinoox\Component\Terminal;
use Pinoox\Portal\App\AppEngine;
use Pinoox\Portal\StubGenerator;
use Pinoox\Support\SystemConfig;
use Pinoox\Terminal\Migrate\SelectsMigrationPackage;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'patch:create',
    description: 'Create a data patch file for one-time DB or config updates',
)]

class PatchCreateCommand extends Terminal
{
    use SelectsMigrationPackage;

    private string $package;
    private string $patch;

    protected function configure(): void
    {
        $this
            ->setHelp('Example: php pinoox patch:create fix_user_roles com_my_shop')
            ->addArgument('patch', InputArgument::REQUIRED, 'Patch name (e.g. fix_user_roles)')
            ->addArgument('package', InputArgument::OPTIONAL, 'App package or platform. Leave empty to pick from the list.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $this->package = $this->resolvePackage($input, $output, new SymfonyStyle($input, $output));
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
        if ($this->package === 'platform') {
            return SystemConfig::platformPath('patches');
        }

        return AppEngine::path($this->package) . '/' . trim(SystemConfig::rawPath('app_patches', 'patches'), '/\\');
    }

    private function getPatchFileName(): string
    {
        $name = Str::toUnderScore(Str::toCamelCase($this->patch));

        return date('Y_m_d_His') . '_' . $name;
    }

    private function getNamespace(): string
    {
        return $this->package === 'platform'
            ? 'Pinoox\\Patches'
            : 'App\\' . $this->package . '\\patches';
    }
}

