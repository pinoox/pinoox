<?php
namespace pinoox\command;


use pinoox\app\com_pinoox_manager\model\AppModel;
use pinoox\component\console;
use pinoox\component\Dir;
use pinoox\component\File;
use pinoox\component\interfaces\CommandInterface;
use pinoox\component\Zip;


class appBilder extends console implements CommandInterface
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = "app:build";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "bulid setup file for application.";

    /**
     * The console command Arguments.
     *
     * @var array
     */
    protected $arguments = [
        ['package', true, 'name of package that you want to build setup file.'],
    ];

    /**
     * The console command Options.
     *
     * @var array
     */
    protected $options = [
        [ 'rewrite' , 'r' , 'Mod if setup file exist: [rewrite(r),version(v),index(i)] for example:[--r=rewrite | --r=r | --rewrite=index | --rewrite=v]' , 'index' ],
    ];

    protected $appPath = null;
    protected $tempPackageName = 'com_pinoox_package_builder_';

    /**
     * Execute the console command.
     *
     */
    public function handle()
    {
        try {
            $app = AppModel::fetch_by_package_name($this->argument('package'));
            if ( is_null($app) )
                $this->error(sprintf('Can not find app with name `%s`!' , $this->argument('package')));
            $this->appPath = Dir::path('~apps/' . $this->argument('package'));
            $ignoreFiles = $this->find_gitignore_files();
            $rules = $this->parse_git_ignore_files($ignoreFiles);
            list($allFolders, $allFiles) = $this->getAllFilesAndFoldersOfApp($this->appPath . DIRECTORY_SEPARATOR, $rules);
            list($acceptedFolders, $acceptedFiles) = $this->checkingFilesAcceptGitIgnore($allFolders, $allFiles, $rules);
            unset($allFolders, $allFiles, $rules, $ignoreFiles);
            $this->makeBuildFile($acceptedFolders, $acceptedFiles, $this->argument('package'));
        } catch (\Exception $exception){
            $this->danger('Something got error during make build file. please do it manually!');
            $this->newLine();
            $this->danger($exception->getMessage());
            $this->newLine();
            File::remove(str_replace(DIRECTORY_SEPARATOR.$this->argument('package') , DIRECTORY_SEPARATOR.$this->tempPackageName.$this->argument('package') ,$this->appPath ));
            $this->error('Some error happened!');
        }
    }

    private function find_gitignore_files()
    {
        $this->startProgressBar(4, 'Find `.gitignore` files.');
        $baseFile = $this->find_gitignore_files_in_dir(Dir::path('~'));
        $this->nextStepProgressBar();
        $appsFile = $this->find_gitignore_files_in_dir(Dir::path('~apps/'));
        $this->nextStepProgressBar();
        $appFile = $this->find_gitignore_files_in_dir($this->appPath, true);
        $this->nextStepProgressBar();
        $result = array_unique(array_merge($appFile, $baseFile, $appsFile));
        $this->nextStepProgressBar();
        $this->finishProgressBar(sprintf('%d file founded.', count($result)));
        return $result;
    }

    private function find_gitignore_files_in_dir($dir, $checkSubDire = false)
    {
        $files = array();
        $FilesInDirectory = File::get_files_by_pattern($dir, ".gitignore");
        $files = array_merge($files, $FilesInDirectory);
        if ($checkSubDire) {
            $dirs = File::get_dir_folders($dir);
            foreach ($dirs as $dir) {
                $FilesInDirectory = $this->find_gitignore_files_in_dir($dir, $checkSubDire);
                $files = array_merge($files, $FilesInDirectory);
            }
        }
        return $files;
    }

    private function parse_git_ignore_files($ignoreFiles)
    { # $file = '/absolute/path/to/.gitignore'
        $this->startProgressBar(count($ignoreFiles), 'Parse `.gitignore` files.');
        $matches = array();
        foreach ($ignoreFiles as $file) {
            $lines = file($file);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '') continue;                 # empty line
                if (substr($line, 0, 1) == '#') continue;# a comment
                $matches[] = $line;
            }
            $matches = array_unique($matches, SORT_REGULAR);
            $this->nextStepProgressBar();
        }
        $this->finishProgressBar(sprintf('%d Rules founded.', count($matches)));
        return $matches;
    }

    private function getAllFilesAndFoldersOfApp($dir, $ignoreRules, $isFirstCall = true)
    {
        if ($isFirstCall) {
            $this->startProgressBar(1, 'Finding all files and sub folders.');
        }
        $files = array();
        $folders = array();
        $FilesInDirectory = File::get_files($dir);
        $files = array_merge($files, $FilesInDirectory);
        $dirs = File::get_dir_folders($dir);
        $folders = array_merge($folders, $dirs);
        $this->nextStepProgressBar(1, count($dirs));
        foreach ($dirs as $dir) {
            list($foldersInDirectory, $FilesInDirectory) = $this->getAllFilesAndFoldersOfApp($dir, $ignoreRules, false);
            $files = array_merge($files, $FilesInDirectory);
            $folders = array_merge($folders, $foldersInDirectory);
        }
        if ($isFirstCall) {
            $this->finishProgressBar(sprintf('Founded %d file and %d folder.', count($files), count($folders)));
        }
        return [$folders, $files];
    }

    private function checkingFilesAcceptGitIgnore($folders, $files, $ignoreRules)
    {
        $numIgnoreRules = count($ignoreRules);
        $this->startProgressBar((count($folders) + count($files)) * $numIgnoreRules, 'Checking files and sub folders.');
        $acceptedFiles = [];
        $acceptedFolder = [];
        $tempAcceptedFolder = [];
        foreach ($folders as $index => $folder) {
            $numCheck = 0;
            foreach ($ignoreRules as $ignoreRule) {
                if (!$this->isPathCurrent(str_replace($this->appPath, '', $folder), '**' . DIRECTORY_SEPARATOR . $ignoreRule . DIRECTORY_SEPARATOR . '**')) {
                    $acceptedFolder[$index] = $folder;
                    $tempAcceptedFolder[$index] = str_replace($this->appPath, '', $folder);
                    $this->nextStepProgressBar();
                    $numCheck++;
                } else {
                    $this->nextStepProgressBar($numIgnoreRules - $numCheck);
                    unset($acceptedFolder[$index],$tempAcceptedFolder[$index]);
                    break;
                }
            }
        }
        foreach ($files as $index => $file) {
            $numCheck = 0;
            foreach ($ignoreRules as $ignoreRule) {
                if (!$this->isPathCurrent(str_replace($this->appPath, '', $file), '**' . DIRECTORY_SEPARATOR . $ignoreRule)) {
                    $fileCheck = str_replace($this->appPath, '', $file);
                    $baseName = basename($fileCheck);
                    if ( in_array(substr($fileCheck , 0 , -1 * strlen($baseName)) , $tempAcceptedFolder) or substr($fileCheck , 0 , -1 * strlen($baseName)) == "\\"){
                        $acceptedFiles[$index] = $file;
                        $this->nextStepProgressBar();
                        $numCheck++;
                    } else {
                        $this->nextStepProgressBar($numIgnoreRules - $numCheck);
                        unset($acceptedFiles[$index]);
                        break;
                    }
                } else {
                    $this->nextStepProgressBar($numIgnoreRules - $numCheck);
                    unset($acceptedFiles[$index]);
                    break;
                }
            }
        }
        $this->finishProgressBar(sprintf('Accepted %d file and %d folder.', count($acceptedFiles), count($acceptedFolder)));
        return [$acceptedFolder, $acceptedFiles];
    }

    private function makeBuildFile($folders, $files, $packageName)
    {
        $DS = DIRECTORY_SEPARATOR;
        $tempPackageName =$this->tempPackageName.$packageName;
        $this->startProgressBar(count($folders) + count($files) + 1 , 'Creating Temp files.');
        File::make_folder(str_replace($DS.$packageName , $DS.$tempPackageName,$this->appPath ), false,0777 , false);
        $this->nextStepProgressBar();
        foreach ($folders as $folder){
            File::make_folder(str_replace($DS.$packageName.$DS , $DS.$tempPackageName.$DS , $folder) , false,0777 , false);
            $this->nextStepProgressBar();
        }
        foreach ($files as $file){
            @copy($file , str_replace($DS.$packageName.$DS , $DS.$tempPackageName.$DS , $file));
            $this->nextStepProgressBar();
        }
        $this->finishProgressBar();
        $this->startProgressBar(count($folders) + count($files) + 3, 'Creating Build file.');
        $setupFileName = $packageName;
        $setupFileIndex = 2;
        while (true) {
            if ( file_exists(Dir::path('~') . $DS . $setupFileName . '.pin')) {
                if ( $this->option('rewrite') == 'rewrite' or $this->option('rewrite') == 'r' )
                    unlink(Dir::path('~') . $DS . $setupFileName . '.pin');
                elseif ( $this->option('rewrite') == 'version' or $this->option('rewrite') == 'v'  ){
                    $app = AppModel::fetch_by_package_name($packageName);
                    if ( isset($app['version_code']) ){
                        $setupFileName = sprintf('%s (v_%d)', $packageName, $app['version_code']);
                        if ( file_exists(Dir::path('~') . $DS . $setupFileName . '.pin')) {
                            unlink(Dir::path('~') . $DS . $setupFileName . '.pin');
                        }
                    }elseif ( isset($app['version']) ){
                        $setupFileName = sprintf('%s (v_%d)', $packageName, $app['version']);
                        if ( file_exists(Dir::path('~') . $DS . $setupFileName . '.pin')) {
                            unlink(Dir::path('~') . $DS . $setupFileName . '.pin');
                        }
                    } else {
                        $setupFileName = sprintf('%s (%d)', $packageName, $setupFileIndex);
                        $setupFileIndex++;
                    }
                } else {
                    $setupFileName = sprintf('%s (%d)', $packageName, $setupFileIndex);
                    $setupFileIndex++;
                }
            } else
                break;
        }
        $this->nextStepProgressBar();
        $zip = $this->Zip(str_replace($DS.$packageName , $DS.$tempPackageName , $this->appPath) , Dir::path('~') .$DS. $setupFileName . '.pin');
        $this->nextStepProgressBar();
        File::remove(str_replace($DS.$packageName , $DS.$tempPackageName,$this->appPath ));
        $this->nextStepProgressBar();
        if ( file_exists(Dir::path('~') . $DS . $setupFileName . '.pin') and $zip ){
            $this->finishProgressBar();
            $this->success(sprintf('Setup file maked in `%s`.' , Dir::path('~') . $setupFileName . '.pin'));
        } else {
            $this->danger('Something got error during make build file. please do it manually!');
            $this->error('Some error happened!');
        }
    }

    /**
     * @param string
     * @param string
     * @return boolean
     */
    private static function isPathCurrent($currentPath, $mask)
    {
        // $path muze obsahovat wildcard (*)
        // Priklady:
        // */contact.html => about/contact.html, ale ne en/about/contact.html
        // en/*/index.html => en/about/index.html, ale ne en/about/references/index.html
        // (tj. nematchuje '/')
        // ALE!
        // about/* => about/index.html i about/references/index.html
        // (tj. wildcard na konci matchuje i '/')

        $currentPath = ltrim($currentPath, '/');
        $mask = ltrim(trim($mask), '/');

        if ($mask === '*') {
            return TRUE;
        }

        // build pattern
        $pattern = strtr(preg_quote($mask, '#'), array(
            '\*\*' => '.*',
            '\*' => '[^/]*',
        ));
        // match
        return (bool)preg_match('#^' . $pattern . '\z#i', $currentPath);
    }


    private function Zip($source, $destination)
    {
        $zip = new \ZipArchive();
        if (!$zip->open($destination, \ZIPARCHIVE::CREATE)) {
            return false;
        }

        $source = str_replace('\\', DIRECTORY_SEPARATOR, realpath($source));

        if (is_dir($source) === true) {
            $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($source), \RecursiveIteratorIterator::SELF_FIRST);

            foreach ($files as $file) {

                $file = str_replace('\\', DIRECTORY_SEPARATOR, $file);

                // Ignore "." and ".." folders
                if (in_array(substr($file, strrpos($file, DIRECTORY_SEPARATOR) + 1), array('.', '..')))
                    continue;

                $file = realpath($file);

                if (is_dir($file) === true) {
                    $zip->addEmptyDir(str_replace($source . DIRECTORY_SEPARATOR, '', $file . DIRECTORY_SEPARATOR));
                    $this->nextStepProgressBar();
                } else if (is_file($file) === true) {
                    $zip->addFromString(str_replace($source . DIRECTORY_SEPARATOR, '', $file), file_get_contents($file));
                    $this->nextStepProgressBar();
                }
            }
        } else if (is_file($source) === true) {
            $zip->addFromString(basename($source), file_get_contents($source));
        }

        return $zip->close();
    }
}