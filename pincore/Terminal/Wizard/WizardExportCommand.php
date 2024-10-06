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

namespace Pinoox\Terminal\Wizard;

use Pinoox\Component\Terminal;
use Pinoox\Portal\App\AppProvider;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use ZipArchive;

#[AsCommand(
    name: 'wizard:export',
    description: 'Export production package',
)]
class WizardExportCommand extends Terminal
{
    const EXPORT_PATH = '/export/';

    // Add 'void' as the return type here
    protected function configure(): void
    {
        $this
            ->addArgument('package', InputArgument::REQUIRED, 'Enter package name')
            ->addOption('format', 'f', InputOption::VALUE_OPTIONAL, 'Export format (pin or zip)', 'pin');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        parent::execute($input, $output);

        $versionCode = app('version-code');
        
        $buildConfig = app('build', [
            'gitignore' => true,  // default to true
            'exclude' => [],      // default to an empty array
        ]);

        $packageName = $input->getArgument('package');
        $format = $input->getOption('format');

        $packagePath = path('~/apps/' . $packageName);
        $exportDir = path('~/apps/' . $packageName . self::EXPORT_PATH);

        if (!is_dir($packagePath)) {
            $this->error("Package not found at: $packagePath");
        }

        if (!is_dir($exportDir)) {
            mkdir($exportDir, 0777, true);
        }

        // Get the export details before confirmation
        $extension = ($format === 'zip') ? 'zip' : 'pin';

        // Append version code to the export file name
        $exportedFile = $exportDir . "{$packageName}_v{$versionCode}_".date('Ymd_i').".$extension";

        $finder = new Finder();
        $this->configureFinder($finder, $packagePath, $buildConfig);

        if (!$this->showExportDetails($finder, $packagePath, $exportedFile, $output)) {
            $this->error('Export canceled by the user.');
        }

        $zip = new ZipArchive();
        if ($zip->open($exportedFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {

            $finder = new Finder();
            $this->configureFinder($finder, $packagePath, $buildConfig);

            $fileCount = iterator_count($finder->files());
            $progressBar = new ProgressBar($output, $fileCount);
            $progressBar->start();

            $this->addFilesToZip($zip, $finder, $progressBar);
            $zip->close();

            $progressBar->finish();
            $output->writeln(""); // Add a new line after progress bar finishes

            $this->success("🎉 Export completed successfully! 🎉\n");

            // Open the folder after export completes
            $this->openFolderInFileManager($exportDir);

            return Command::SUCCESS;
        }

        $this->error('Failed to create export file');
    }

    private function configureFinder(Finder $finder, string $packagePath, array $buildConfig): void
    {
        $finder
            ->in($packagePath)
            ->files()
            ->ignoreVCS(true)
            ->ignoreUnreadableDirs();

        // Check if gitignore should be respected
        if (!empty($buildConfig['gitignore']) && $buildConfig['gitignore'] === true) {
            $finder->ignoreVCSIgnored(true);
        }

        // Handle exclude paths and wildcard expansion
        if (!empty($buildConfig['exclude'])) {
            foreach ($buildConfig['exclude'] as $excludePath) {
                if (str_contains($excludePath, '*')) {
                    $this->excludeWildcardPaths($finder, $packagePath, $excludePath);
                } else {
                    $absolutePath = $packagePath . '/' . $excludePath;
                    if (is_dir($absolutePath)) {
                        $finder->notPath($excludePath);
                    } elseif (is_file($absolutePath)) {
                        $finder->notPath($absolutePath);
                    }
                }
            }
        }

        // Handle include_themes logic specifically for the theme directory
        $themeBasePath = $packagePath . '/theme';

        if (!empty($buildConfig['include_themes'])) {
            // Find all subdirectories in the theme folder
            $themeFinder = new Finder();
            $themeFinder->in($themeBasePath)->directories()->depth(0);

            $themesToExclude = [];
            foreach ($themeFinder as $dir) {
                $themeName = $dir->getRelativePathname();
                // If the theme is not in include_themes, mark it for exclusion
                if (!in_array($themeName, $buildConfig['include_themes'])) {
                    $themesToExclude[] = 'theme/' . $themeName;
                }
            }

            // Exclude the themes that are not in include_themes
            foreach ($themesToExclude as $themeToExclude) {
                $finder->notPath($themeToExclude);
            }
        }
    }

    private function excludeWildcardPaths(Finder $finder, string $packagePath, string $wildcardPath): void
    {
        // Convert wildcard path to a base directory and pattern (e.g., "theme/*/src" -> base: "theme", pattern: "*/src")
        $parts = explode('/*', $wildcardPath, 2);
        $baseDir = $parts[0];
        $remainingPath = isset($parts[1]) ? trim($parts[1], '/') : '';

        // Use Finder to locate actual directories and files matching the pattern
        $subDirectories = (new Finder())
            ->in($packagePath . '/' . $baseDir)
            ->directories()
            ->depth(0)
            ->name('*')
            ->sortByName();

        foreach ($subDirectories as $dir) {
            $actualPath = $dir->getRealPath() . '/' . $remainingPath;

            if (is_dir($actualPath)) {
                $relativePath = str_replace($packagePath . '/', '', $actualPath);
                $finder->notPath($relativePath);
            } elseif (is_file($actualPath)) {
                $relativePath = str_replace($packagePath . '/', '', $actualPath);
                $finder->notPath($relativePath);
            }
        }
    }


    private function addFilesToZip(ZipArchive $zip, Finder $finder, ProgressBar $progressBar): void
    {
        foreach ($finder->files() as $file) {
            $filePath = $file->getRealPath();
            $relativePath = $file->getRelativePathname();

            // Add the file to the zip
            $zip->addFile($filePath, $relativePath);
            $progressBar->advance();
        }
    }

    private function showExportDetails(Finder $finder, string $packagePath, string $exportedFile, OutputInterface $output): bool
    {
        // Show only top-level folders and use manual exclusion logic
        $finder->in($packagePath)->directories()->depth(0);

        $output->writeln("Package: <info>$packagePath</info>");
        $output->writeln("Export file: <info>$exportedFile</info>");
        $output->writeln("Top-level folders to be included or ignored:");

        foreach ($finder as $directory) {
            $relativePath = $directory->getRelativePathname();
            $output->writeln("<fg=green> - $relativePath (included)</>");
        }

        // Prompt user to confirm
        $output->writeln("");
        $confirmation = $this->askConfirmation("Do you want to proceed with the export?", $output);
        return $confirmation;
    }

    private function askConfirmation(string $question, OutputInterface $output): bool
    {
        $output->writeln("<question>$question</question> (y/n)");

        // Capture user input
        $input = trim(fgets(STDIN));

        return strtolower($input) === 'y';
    }

    private function openFolderInFileManager(string $exportDir): void
    {
        if (PHP_OS_FAMILY === 'Windows') {
            exec('explorer ' . escapeshellarg($exportDir));
        } elseif (PHP_OS_FAMILY === 'Darwin') {
            exec('open ' . escapeshellarg($exportDir));
        } elseif (PHP_OS_FAMILY === 'Linux') {
            exec('xdg-open ' . escapeshellarg($exportDir));
        }
    }
}