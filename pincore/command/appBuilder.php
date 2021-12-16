<?php
namespace pinoox\command;


use pinoox\app\com_pinoox_manager\model\AppModel;
use pinoox\component\console;
use pinoox\component\Dir;
use pinoox\component\File;
use pinoox\component\HelperString;
use pinoox\component\interfaces\CommandInterface;
use pinoox\component\Zip;


class appBuilder extends console implements CommandInterface
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
        ['package', false, 'name of package that you want to build setup file.' , null ],
    ];

    /**
     * The console command Options.
     *
     * @var array
     */
    protected $options = [
        [ 'rewrite' , 'r' , 'Mod if setup file exist: [rewrite(r),version(v),index(i)] for example:[--r=rewrite | --r=r | --rewrite=index | --rewrite=v]' , 'index' ],
    ];

    private $appPath = null;
    private $package = null ;
    private $tempPackageName = 'com_pinoox_package_builder_';

    /**
     * Execute the console command.
     *
     */
    public function handle()
    {
        try {
            $this->package = $this->argument('package');
            if ( $this->package == null ){
                $apps = AppModel::fetch_all(null , true);
                $apps = array_keys($apps);
                $appId = $this->choice('Please select package you want to build setup file.',  $apps );
                $this->package = isset($apps[$appId]) ? $apps[$appId] : null ;
                if ( $this->package == null ){
                    $this->error('Can not find selected package!');
                }
            }
            $app = AppModel::fetch_by_package_name($this->package);
            if ( is_null($app) )
                $this->error(sprintf('Can not find app with name `%s`!' , $this->package));
            $this->appPath = Dir::path('~apps/' . $this->package);
            $ignoreFiles = $this->find_gitignore_files();
            $rules = $this->parse_git_ignore_files($ignoreFiles);
            list($allFolders, $allFiles) = $this->getAllFilesAndFoldersOfApp($this->appPath . DIRECTORY_SEPARATOR);
            list($acceptedFolders, $acceptedFiles) = $this->checkingFilesAcceptGitIgnore($allFolders, $allFiles, $rules);
            unset($allFolders, $allFiles, $rules, $ignoreFiles);
            $this->makeBuildFile($acceptedFolders, $acceptedFiles, $this->package);
        } catch (\Exception $exception){
            $this->danger('Something got error during make build file. please do it manually!');
            $this->newLine();
            $this->danger($exception->getMessage());
            $this->newLine();
            sleep(1);
            gc_collect_cycles();
            File::remove(str_replace(DIRECTORY_SEPARATOR.$this->package , DIRECTORY_SEPARATOR.$this->tempPackageName.$this->package ,$this->appPath ));
            $this->error('Some error happened!');
        }
    }

    private function find_gitignore_files()
    {
        $app = AppModel::fetch_by_package_name($this->package);
        if ( ! isset($app['build']['gitignore']) or ( isset($app['build']['gitignore']) and $app['build']['gitignore'] )) {
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
            return [];
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
        $this->startProgressBar(count($ignoreFiles) + 1 , 'Parse `.gitignore` files.');
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
        $this->nextStepProgressBar();
        $app = AppModel::fetch_by_package_name($this->package);
        if ( isset($app['build']['explode']) and ! is_null($app['build']['explode']))
            if ( is_array($app['build']['explode']))
                $matches = array_merge($matches , $app['build']['explode']) ;
            elseif ( is_string($app['build']['explode']) )
                $matches[] = $app['build']['explode'];
        $matches = array_unique($matches, SORT_REGULAR);
        $this->finishProgressBar(sprintf('%d Rules founded.', count($matches)));
        return $matches;
    }

    private function getAllFilesAndFoldersOfApp($dir, $isFirstCall = true)
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
            list($foldersInDirectory, $FilesInDirectory) = $this->getAllFilesAndFoldersOfApp($dir, false);
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
        $app = AppModel::fetch_by_package_name($this->package);
        $implodeDirs = array();
        if ( isset($app['build']['implode']) and ! is_null($app['build']['implode']))
            if ( is_array($app['build']['implode']))
                $implodeDirs =  $app['build']['implode'];
            elseif ( is_string($app['build']['implode']) )
                $implodeDirs = array($app['build']['implode']);
        $numIgnoreRules = count($ignoreRules) + count($implodeDirs);
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
            foreach ( $implodeDirs as $implodeDir ){
                $lastStatus = isset($acceptedFolder[$index]);
                $implodeDir = str_replace(['\\' , '/'] ,  DIRECTORY_SEPARATOR, $implodeDir);
                $implodeDirsIn = explode(DIRECTORY_SEPARATOR, $implodeDir);
                $lastImplodeDirIn = "";
                foreach ( $implodeDirsIn as $ti => $implodeDirIn ) {
                    if ( $ti == 0)
                        $lastImplodeDirIn = $implodeDirIn;
                    if ( $ti > 0 )
                        $lastImplodeDirIn .= DIRECTORY_SEPARATOR.$implodeDirIn;
                    if ($this->isPathCurrent(str_replace($this->appPath, '', $folder), '**' . DIRECTORY_SEPARATOR . $lastImplodeDirIn . DIRECTORY_SEPARATOR . '**')) {
                        $acceptedFolder[$index] = $folder;
                    } elseif ( ! $lastStatus ) {
                            unset($acceptedFolder[$index]);
                            break;
                    }

                }
                $this->nextStepProgressBar();
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
            foreach ( $implodeDirs as $implodeDir ){
                $implodeDir = str_replace(['\\' , '/'] ,  DIRECTORY_SEPARATOR, $implodeDir);
                if ($this->isPathCurrent(str_replace($this->appPath, '', $file), '**' . DIRECTORY_SEPARATOR . $implodeDir . DIRECTORY_SEPARATOR . '**') or $this->isPathCurrent(str_replace($this->appPath, '', $file), '**' . DIRECTORY_SEPARATOR . $implodeDir)) {
                    $acceptedFiles[$index] = $file;
                }
                $this->nextStepProgressBar();
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
            File::generate(str_replace($DS.$packageName.$DS , $DS.$tempPackageName.$DS , $folder) .$DS . 'make.pin' , 'test');
            unlink(str_replace($DS.$packageName.$DS , $DS.$tempPackageName.$DS , $folder) .$DS . 'make.pin');
            $this->nextStepProgressBar();
        }
        foreach ($files as $file){
            @copy($file , str_replace($DS.$packageName.$DS , $DS.$tempPackageName.$DS , $file));
            $this->nextStepProgressBar();
        }
        $this->finishProgressBar();
        $this->startProgressBar(count($folders) + count($files) + 3, 'Creating Build file.');
        $setupFileNameShouldBe = $packageName;
        $app = AppModel::fetch_by_package_name($packageName);
        if ( isset($app['build']['filename']))
            $setupFileNameShouldBe = $app['build']['filename'];
        $setupFileName = $setupFileNameShouldBe;
        $setupFileIndex = 2;
        while (true) {
            if ( file_exists(Dir::path('~') . $DS . $setupFileName . '.pin')) {
                if ( $this->option('rewrite') == 'rewrite' or $this->option('rewrite') == 'r' )
                    unlink(Dir::path('~') . $DS . $setupFileName . '.pin');
                elseif ( $this->option('rewrite') == 'version' or $this->option('rewrite') == 'v'  ){
                    if ( isset($app['version_code']) ){
                        $setupFileName = sprintf('%s (v_%d)', $setupFileNameShouldBe, $app['version_code']);
                        if ( file_exists(Dir::path('~') . $DS . $setupFileName . '.pin')) {
                            unlink(Dir::path('~') . $DS . $setupFileName . '.pin');
                        }
                    }elseif ( isset($app['version']) ){
                        $setupFileName = sprintf('%s (v_%d)', $setupFileNameShouldBe, $app['version']);
                        if ( file_exists(Dir::path('~') . $DS . $setupFileName . '.pin')) {
                            unlink(Dir::path('~') . $DS . $setupFileName . '.pin');
                        }
                    } else {
                        $setupFileName = sprintf('%s (%d)', $setupFileNameShouldBe, $setupFileIndex);
                        $setupFileIndex++;
                    }
                } else {
                    $setupFileName = sprintf('%s (%d)', $setupFileNameShouldBe, $setupFileIndex);
                    $setupFileIndex++;
                }
            } else
                break;
        }
        $this->nextStepProgressBar();
        $zip = $this->Zip(str_replace($DS.$packageName , $DS.$tempPackageName , $this->appPath) , Dir::path('~') .$DS. $setupFileName . '.pin');
        $this->nextStepProgressBar();
        sleep(1);
        gc_collect_cycles();
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
        $mask = str_replace(['\\','/'] , DIRECTORY_SEPARATOR , trim($mask) );
        $mask = HelperString::lastDelete($mask , DIRECTORY_SEPARATOR);
        $mask = ltrim( $mask, '/');

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