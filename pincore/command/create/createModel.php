<?php

namespace pinoox\command\create;


use mysql_xdevapi\Exception;
use pinoox\component\ClassBuilder;
use pinoox\component\console;
use pinoox\component\File;
use pinoox\component\helpers\Str;
use pinoox\component\HelperString;
use pinoox\component\interfaces\CommandInterface;


class createModel extends console implements CommandInterface
{
    /**
     * The console command name.
     *
     * @var string
     */
    protected $signature = "create:model";

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = "Create a new model class";

    /**
     * The console command Arguments.
     *
     * @var array
     */
    protected $arguments = [
        ['model', true, 'Model Name'],
        ['package', false, 'package name of app'],
    ];

    /**
     * The console command Options.
     *
     * @var array
     */
    protected $options = [
        ['extends', 'e', 'namespace of extends class', 'Model'],
        ["author", "a", "Code author, for copyright in source code.", 'Pinoox'],
        ["link", "l", "Author Connect Link, for copyright in source code.", 'https://www.pinoox.com/'],
        ["license", null, "Put your license in source code (for example:`MIT`).", null],
        ["pinlogo", null, "if write this,pinoox logo into the in source code.", null],
        ["ignoreCopyright", 'i', "Don't show any copyright in source.", null],
        ["migration", 'm', "Create a database migration", true],
    ];

    protected $extend = null;
    protected $use = null;
    protected $model = null;
    protected $modelPath = null;

    /**
     * Execute the console command.
     *
     */
    public function handle()
    {
        $this->model = Str::toCamelCase($this->argument('model'));
        $package = $this->argument('package');
        $this->chooseApp($package);
        $this->setPath();
        $this->setExtend();

        $this->check();
        $this->createModel();

        if (self::hasOption('migration', $this->options)) {
            $this->execute('create:migration ' . $this->model);
        }
    }

    private function setPath()
    {
        $this->modelPath = $this->cli['path'] . '\\model\\' . ucfirst($this->model) . '.php';
    }

    private function setExtend()
    {
        $extend = $this->option('extends');
        if (strtolower($extend) == 'model') {
            $this->use = 'pinoox\component\database\Model';
            $this->extend = 'Model';
        }
    }

    private function check()
    {
        if (file_exists($this->modelPath)){
          $this->error('☓  The model name "' . $this->model . '" already exists ');
        }
    }

    private function createModel(): void
    {
        try {
            $builder = ClassBuilder::init($this->model)
                ->extends($this->extend)
                ->namespace($this->cli['namespace'] . '\model')
                ->use($this->use)
                ->build()
                ->export($this->modelPath);
            if ($builder->isSuccess()) {
                $this->success(sprintf('Model created in "%s".', str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $this->modelPath)));
                $this->newLine();
            } else {
                $this->error(sprintf('Same file exist in "%s"!', str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $this->modelPath)));
            }
        } catch (\Exception $e) {
            $this->error($e);
        }
    }

}