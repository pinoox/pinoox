<?php
/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Pinoox
 * @link https://www.pinoox.com/
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace Pinoox\Terminal\Database;

use Pinoox\Component\Terminal;
use Pinoox\Portal\Database\DB;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'query',
    description: 'Execute SQL queries directly from command line'
)]
class QueryCommand extends Terminal
{
    protected function configure(): void
    {
        $this
            ->addArgument('sql', InputArgument::REQUIRED, 'SQL query to execute')
            ->addOption('raw', 'r', InputOption::VALUE_NONE, 'Output raw results without table formatting')
            ->addOption('limit', 'l', InputOption::VALUE_OPTIONAL, 'Limit number of results displayed', 100)
            ->addOption('connection', 'c', InputOption::VALUE_OPTIONAL, 'Database connection name', 'default')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Show query without executing it')
            ->addOption('confirm', null, InputOption::VALUE_NONE, 'Require confirmation for destructive queries');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $sql = $input->getArgument('sql');
        $raw = $input->getOption('raw');
        $limit = (int) $input->getOption('limit');
        $connection = $input->getOption('connection');
        $dryRun = $input->getOption('dry-run');
        $confirm = $input->getOption('confirm');

        // Show query if dry-run
        if ($dryRun) {
            $output->writeln("<info>Query to execute:</info>");
            $output->writeln("<comment>{$sql}</comment>");
            return Command::SUCCESS;
        }

        // Check for destructive operations
        if ($this->isDestructiveQuery($sql) && ($confirm || $this->shouldConfirm($sql))) {
            $helper = $this->getHelper('question');
            $question = new \Symfony\Component\Console\Question\ConfirmationQuestion(
                "<question>This query may modify data. Are you sure you want to continue? (y/N)</question> ",
                false
            );

            if (!$helper->ask($input, $output, $question)) {
                $output->writeln("<info>Query cancelled.</info>");
                return Command::SUCCESS;
            }
        }

        try {
            // Set connection if specified
            if ($connection !== 'default') {
                DB::setDefaultConnection($connection);
            }

            $startTime = microtime(true);
            
            // Determine query type
            $queryType = $this->getQueryType($sql);
            
            if ($queryType === 'SELECT') {
                $results = DB::select($sql);
                $this->displaySelectResults($results, $output, $raw, $limit);
            } else {
                $affected = DB::statement($sql);
                $this->displayStatementResults($queryType, $affected, $output);
            }

            $endTime = microtime(true);
            $executionTime = round(($endTime - $startTime) * 1000, 2);
            
            $output->writeln("");
            $output->writeln("<info>Query executed in {$executionTime}ms</info>");

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $output->writeln("<error>Error executing query:</error>");
            $output->writeln("<error>{$e->getMessage()}</error>");
            return Command::FAILURE;
        }
    }

    private function displaySelectResults(array $results, OutputInterface $output, bool $raw, int $limit): void
    {
        if (empty($results)) {
            $output->writeln("<comment>No results found.</comment>");
            return;
        }

        // Limit results if specified
        if ($limit > 0 && count($results) > $limit) {
            $results = array_slice($results, 0, $limit);
            $output->writeln("<comment>Showing first {$limit} results...</comment>");
        }

        if ($raw) {
            // Raw output
            foreach ($results as $row) {
                $output->writeln(json_encode($row, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        } else {
            // Table output
            $table = new Table($output);
            
            // Set headers from first row
            if (is_array($results[0])) {
                $headers = array_keys($results[0]);
                $table->setHeaders($headers);
                
                // Add rows
                foreach ($results as $row) {
                    $table->addRow(array_values($row));
                }
            } else {
                // Handle object results
                $firstRow = (array) $results[0];
                $headers = array_keys($firstRow);
                $table->setHeaders($headers);
                
                foreach ($results as $row) {
                    $table->addRow(array_values((array) $row));
                }
            }
            
            $table->render();
        }

        $output->writeln("");
        $output->writeln("<info>Total results: " . count($results) . "</info>");
    }

    private function displayStatementResults(string $queryType, $affected, OutputInterface $output): void
    {
        switch ($queryType) {
            case 'INSERT':
                $output->writeln("<info>Insert successful. Last insert ID: {$affected}</info>");
                break;
            case 'UPDATE':
            case 'DELETE':
                $output->writeln("<info>{$queryType} successful. Affected rows: {$affected}</info>");
                break;
            case 'CREATE':
            case 'ALTER':
            case 'DROP':
                $output->writeln("<info>{$queryType} statement executed successfully.</info>");
                break;
            default:
                $output->writeln("<info>Statement executed successfully.</info>");
        }
    }

    private function getQueryType(string $sql): string
    {
        $sql = trim(strtoupper($sql));
        
        if (strpos($sql, 'SELECT') === 0) return 'SELECT';
        if (strpos($sql, 'INSERT') === 0) return 'INSERT';
        if (strpos($sql, 'UPDATE') === 0) return 'UPDATE';
        if (strpos($sql, 'DELETE') === 0) return 'DELETE';
        if (strpos($sql, 'CREATE') === 0) return 'CREATE';
        if (strpos($sql, 'ALTER') === 0) return 'ALTER';
        if (strpos($sql, 'DROP') === 0) return 'DROP';
        if (strpos($sql, 'TRUNCATE') === 0) return 'TRUNCATE';
        
        return 'UNKNOWN';
    }

    private function isDestructiveQuery(string $sql): bool
    {
        $sql = trim(strtoupper($sql));
        $destructiveKeywords = ['DELETE', 'DROP', 'TRUNCATE', 'UPDATE'];
        
        foreach ($destructiveKeywords as $keyword) {
            if (strpos($sql, $keyword) === 0) {
                return true;
            }
        }
        
        return false;
    }

    private function shouldConfirm(string $sql): bool
    {
        $sql = trim(strtoupper($sql));
        
        // Always confirm for these operations
        $alwaysConfirm = ['DROP', 'TRUNCATE'];
        foreach ($alwaysConfirm as $keyword) {
            if (strpos($sql, $keyword) === 0) {
                return true;
            }
        }
        
        // Confirm DELETE without WHERE clause
        if (strpos($sql, 'DELETE') === 0 && strpos($sql, 'WHERE') === false) {
            return true;
        }
        
        // Confirm UPDATE without WHERE clause
        if (strpos($sql, 'UPDATE') === 0 && strpos($sql, 'WHERE') === false) {
            return true;
        }
        
        return false;
    }
} 