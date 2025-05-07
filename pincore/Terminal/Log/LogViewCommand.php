<?php

namespace Pinoox\Terminal\Log;

use Pinoox\Component\Terminal;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'log:view',
    description: 'View or open the Pinoox log file'
)]
class LogViewCommand extends Terminal
{
    protected function configure(): void
    {
        $this
            ->addOption('tail', 't', InputOption::VALUE_OPTIONAL, 'Show the last N lines of the log', 10)
            ->addOption('open', 'o', InputOption::VALUE_NONE, 'Open the log file with the default text editor')
            ->addOption('follow', 'f', InputOption::VALUE_NONE, 'Follow the log file (tail -f)')
            ->addOption('find', null, InputOption::VALUE_NONE, 'Find the log file if it\'s not in the default location');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        // Get log file path from config
        $config = \Pinoox\Portal\Config::file('pinoox')->get('log');
        $logPath = $config['path'] ?? (sys_get_temp_dir() . '/pinoox.log');
        
        // Check if the log file exists
        if (!file_exists($logPath)) {
            $output->writeln("<comment>Log file not found at: {$logPath}</comment>");
            
            // If find option is set, try to locate the log file
            if ($input->getOption('find')) {
                $output->writeln("<info>Searching for log file...</info>");
                $foundPath = $this->findLogFile($output);
                if ($foundPath) {
                    $logPath = $foundPath;
                    $output->writeln("<info>Log file found at: {$logPath}</info>");
                } else {
                    $output->writeln("<error>Could not find log file</error>");
                    return Command::FAILURE;
                }
            } else {
                $output->writeln("<info>Use --find option to search for the log file</info>");
                return Command::FAILURE;
            }
        }
        
        // Display file info
        $size = filesize($logPath);
        $sizeFormatted = $this->formatSize($size);
        $output->writeln("<info>Log file:</info> {$logPath}");
        $output->writeln("<info>Size:</info> {$sizeFormatted}");
        
        // If open option is set, open the file with default application
        if ($input->getOption('open')) {
            $this->openLogFile($logPath, $output);
            return Command::SUCCESS;
        }
        
        // If follow option is set, continuously follow the log file
        if ($input->getOption('follow')) {
            $output->writeln("<info>Following log file. Press Ctrl+C to exit.</info>");
            system('tail -f ' . escapeshellarg($logPath));
            return Command::SUCCESS;
        }
        
        // Show the last N lines of the log file
        $tailLines = (int) $input->getOption('tail');
        if ($tailLines > 0) {
            $output->writeln("<info>Last {$tailLines} lines of the log:</info>");
            $output->writeln("<comment>-------------------------------------------------</comment>");
            
            // Get the last N lines
            $lines = $this->getTailOfFile($logPath, $tailLines);
            foreach ($lines as $line) {
                // Highlight different log levels with different colors
                if (strpos($line, '.DEBUG:') !== false) {
                    $output->writeln($line);
                } elseif (strpos($line, '.INFO:') !== false) {
                    $output->writeln("<info>{$line}</info>");
                } elseif (strpos($line, '.WARNING:') !== false || strpos($line, '.NOTICE:') !== false) {
                    $output->writeln("<comment>{$line}</comment>");
                } elseif (strpos($line, '.ERROR:') !== false || strpos($line, '.CRITICAL:') !== false ||
                         strpos($line, '.ALERT:') !== false || strpos($line, '.EMERGENCY:') !== false) {
                    $output->writeln("<error>{$line}</error>");
                } else {
                    $output->writeln($line);
                }
            }
            $output->writeln("<comment>-------------------------------------------------</comment>");
        }
        
        // Display available commands for working with the log
        $output->writeln("\n<info>Available commands:</info>");
        $output->writeln("  <comment>tail -f {$logPath}</comment> - Follow log in real-time");
        $output->writeln("  <comment>tail -100 {$logPath}</comment> - Show last 100 lines");
        $output->writeln("  <comment>grep 'ERROR' {$logPath}</comment> - Find all error messages");
        $output->writeln("  <comment>cat {$logPath} | grep -i 'keyword'</comment> - Search for keyword");
        
        return Command::SUCCESS;
    }
    
    /**
     * Open the log file with the default text editor based on OS
     */
    private function openLogFile(string $path, OutputInterface $output): void
    {
        $os = PHP_OS;
        $command = '';
        
        if (strpos($os, 'WIN') !== false) {
            // Windows
            $command = 'start "" ' . escapeshellarg($path);
        } elseif (strpos($os, 'Darwin') !== false) {
            // macOS
            $command = 'open ' . escapeshellarg($path);
        } else {
            // Linux and others, try to use xdg-open
            $command = 'xdg-open ' . escapeshellarg($path);
        }
        
        $output->writeln("<info>Opening log file with default editor...</info>");
        system($command);
    }
    
    /**
     * Format file size to a human-readable format
     */
    private function formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $factor = floor((strlen($bytes) - 1) / 3);
        return sprintf("%.2f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }
    
    /**
     * Get the last N lines of a file
     */
    private function getTailOfFile(string $filePath, int $lines): array
    {
        $result = [];
        $f = fopen($filePath, "r");
        
        if (filesize($filePath) === 0) {
            fclose($f);
            return [];
        }
        
        // Jump to the end of the file
        fseek($f, 0, SEEK_END);
        
        // Store the current position
        $position = ftell($f);
        
        // Read backwards until we have read $lines lines or reached the start
        $chunk = 4096;
        $linesFound = 0;
        
        // Loop until we have enough lines or reach the start of the file
        while ($position > 0 && $linesFound < $lines) {
            // Calculate how many bytes to read
            $readSize = min($chunk, $position);
            $position -= $readSize;
            
            // Read the chunk
            fseek($f, $position, SEEK_SET);
            $text = fread($f, $readSize);
            
            // Count how many new lines we found
            $linesFound += substr_count($text, "\n");
            
            // If we found more than needed, break
            if ($linesFound >= $lines) {
                break;
            }
        }
        
        // Now start from where we stopped and read to the end
        fseek($f, $position, SEEK_SET);
        $text = fread($f, filesize($filePath) - $position);
        
        // Split by new lines and take only the last $lines lines
        $allLines = explode("\n", $text);
        
        // Skip any empty line at the start after our split
        if (empty($allLines[0])) {
            array_shift($allLines);
        }
        
        // Take only the last $lines lines
        $result = array_slice($allLines, -$lines);
        
        fclose($f);
        return $result;
    }
    
    /**
     * Find the log file if it's not in the default location
     */
    private function findLogFile(OutputInterface $output): ?string
    {
        $output->writeln("<info>Searching for pinoox.log file...</info>");
        
        $os = PHP_OS;
        $command = '';
        
        if (strpos($os, 'Darwin') !== false) {
            // macOS
            $command = 'find /var/folders -name "pinoox.log" -type f 2>/dev/null';
        } elseif (strpos($os, 'WIN') !== false) {
            // Windows - this is more complex, but try a basic approach
            $command = 'where /r %TEMP% pinoox.log';
        } else {
            // Linux and others
            $command = 'find /tmp -name "pinoox.log" -type f 2>/dev/null';
        }
        
        $result = [];
        exec($command, $result, $returnVar);
        
        if ($returnVar === 0 && !empty($result) && file_exists($result[0])) {
            return $result[0];
        }
        
        return null;
    }
} 