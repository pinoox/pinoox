<?php
/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Pinoox
 * @link https://www.pinoox.com/
 * @link https://www.pinoox.com/
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace Pinoox\Terminal\Migrate;

use Pinoox\Component\Terminal;
use Pinoox\Portal\MigrationToolkit;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'migrate:status',
    description: 'Show the status of each migration.',
)]
class MigrateStatusCommand extends Terminal
{
    private string $package;

    /**
     * @var MigrationToolkit
     */
    private $toolkit = null;

    protected function configure(): void
    {
        $this->addArgument('package', InputArgument::REQUIRED, 'Enter the package name of app');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $this->package = $input->getArgument('package');

        $this->init();
        $this->status();

        return Command::SUCCESS;
    }

    private function init()
    {

        $this->toolkit = MigrationToolkit::package($this->package)
            ->action('status')
            ->load();

        if (!$this->toolkit->isSuccess()) {
            $this->error($this->toolkit->getErrors());
        }
    }

    private function status()
    {
        $migrations = $this->toolkit->getMigrations();

        if (empty($migrations)) {
            $this->success('There are no migrations!');
            $this->stop();
        }

        $this->success('Migration status:');
        $this->newLine();
       $this->table(['Migration', 'Batch', 'Status'], $this->getRows($migrations));
    }

    private function getRows($migrations): array
    {
        $rows = [];
        foreach ($migrations as $m) {
            $status = !empty($m['sync']) ? 'Done' : 'Pending';
            $batch = $m['sync']['batch'] ?? null;
            $rows[] = [
                $m['fileName'],
                $batch,
                $status
            ];
        }
        return $rows;
    }

}