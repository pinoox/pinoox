<?php
namespace pinoox\command;


use PHPUnit\Framework\TestCase;
use pinoox\app\com_pinoox_manager\model\AppModel;
use pinoox\component\console;
use pinoox\component\Dir;
use pinoox\component\File;
use pinoox\component\HelperString;
use pinoox\component\interfaces\CommandInterface;


class makeTest extends console implements CommandInterface
{

	/**
	* The console command name.
	*
	* @var string
	*/
	protected $signature = "test:make";

	/**
	* The console command description.
	*
	* @var string
	*/
	protected $description = "Make new PHPUnit test case.";

	/**
	* The console command Arguments.
	*
	*	[ name , is_required , description , default ],
	*
	* @var array
	*/
	protected $arguments = [
        [ 'test_case' , true , 'path and name of test case.'  ],
        [ 'package_name' , true , 'name of application.'  ],
	];

	/**
	* The console command Options.
	*
	*	[ name , short_name , description , default ],
	*
	* @var array
	*/
	protected $options = [
        [ "author" , "a" , "Code author, for copyright in source code." , 'Pinoox' ],
        [ "link" , "l" , "Author Connect Link, for copyright in source code." , 'https://www.pinoox.com/' ],
        [ "license" , null , "Put your license in source code (for example:`MIT`)." , null ],
        [ "pinlogo" , null , "if write this,pinoox logo into the in source code." , null ],
        [ "ignoreCopyright" , 'i' , "Don't show any copyright in source." , null ],
	];


    protected $nameSpaceOfTestFolder = null;
    protected $nameSpaceOfTest = null;
    protected $test = null;
    protected $testPath = null;
    protected $extend = null;
    protected $use = null;
    protected $package = null;

	/**
	* Execute the console command.
	*
	* use full method :
	*   $this->option(string $key) : string|null|bool
	*   $this->argument(string $key) : string|null|bool
	*   $this->hasOption(string $key) : bool
	*   $this->success(string $text) : void
	*   $this->danger(string $text) : void
	*   $this->warning(string $text) : void
	*   $this->info(string $text) : void
	*   $this->gray(string $text) : void
	*   $this->newLine() : void
	*   $this->error(string $text, bool $exit = true) : void
	*   $this->choice(string $question, array $choices, mix $default = null, bool $multiple = false, int $attempts = 2 ) : string|int|array
	*   $this->confirm(string $operation) : bool
	*   $this->table(array $headers, 2D_array $rows) : void
	*   $this->startProgressBar(int $jobs = 1, int $totalJobs = 0 ) : void
	*   $this->nextStepProgressBar(string $operation) : void
	*   $this->finishProgressBar(string $description = "") : void
	*
	*/
    public function handle()
    {
        $this->package =  $this->argument('package_name');
        if ( $this->package != '~' ) {
            $app = AppModel::fetch_by_package_name($this->package);
            if (is_null($app))
                $this->error(sprintf('Can not find app with name `%s`!', $this->package));
        }

        $this->testPath = Dir::path('~apps/' . $this->package.'/tests');
        if ( $this->package == '~' )
            $this->testPath = Dir::path('~tests');
        
        $this->nameSpaceOfTestFolder =  'pinoox\app\\'.$this->package.'\\tests';
        if ( $this->package == '~' )
            $this->nameSpaceOfTestFolder = 'pinoox\tests';

        $Test = explode('\\' , str_replace('/' , '\\' , $this->argument('test_case') ));
        $this->test = array_pop($Test);
        $TestScope = implode('\\' , $Test);
        $this->nameSpaceOfTest = $this->nameSpaceOfTestFolder . (( count($Test) > 0 ) ? '\\'.$TestScope : "");

        $this->testPath = $this->testPath . ( ( count($Test) > 0 ) ? '/'.implode('/' , $Test) : "" ) . '/'.ucfirst(strtolower($this->test)) .'Test.php';
        $this->makeTest();

        $this->error(sprintf('Can not Create Test in "%s"!' , $this->testPath ));
        $this->newLine();
        exit;
    }

    private function makeTest()
    {
        $code = "<?php \n";
        $code .= $this->makeCopyWriteCode();
        $code .= $this->makeNameSpace();
        $code .= "use PHPUnit\Framework\TestCase;\n\n";
        $code .= sprintf("class %sTest extends TestCase\n" , ucfirst(strtolower($this->test)) );
        $code .= "{\n\n";
        $code .= "\tpublic function test_example()\n";
        $code .= "\t{\n";
        $code .= "\t\t".'$this->assertEquals(\'Test String\' , \'Test String\' );'."\n";
        $code .= "\t}\n\n";
        $code .= "}\n";
        $this->makeFile($code);
    }

    private function makeCopyWriteCode(){
        if ( self::hasOption('ignoreCopyright' , $this->options) )
            return "";
        $code ="/**\n";
        if ( self::hasOption('pinlogo' , $this->options) ) {
            $code .= " *      ****  *  *     *  ****  ****  *    *\n";
            $code .= " *      *  *  *  * *   *  *  *  *  *   *  *\n";
            $code .= " *      ****  *  *  *  *  *  *  *  *    *\n";
            $code .= " *      *     *  *   * *  *  *  *  *   *  *\n";
            $code .= " *      *     *  *    **  ****  ****  *    *\n";
        } else {
            $code .= " *\n";
        }
        $code .= " *\n";
        $code .= self::hasOption('author' , $this->options) ? sprintf(" * @author   %s\n" , self::option('author')) : "";
        $code .= self::hasOption('link' , $this->options) ? sprintf(" * @link %s\n" , self::option('link')) : "";
        $code .= self::hasOption('license' , $this->options) ? sprintf(" * @license  %s\n" , self::option('license') == 'MIT' ? 'https://opensource.org/licenses/MIT MIT License' : self::option('license') ) : "";
        $code .=" */\n\n";
        return $code;
    }

    private function makeNameSpace(){
        return sprintf("namespace %s;\n\n",$this->nameSpaceOfTest);
    }

    private function makeFile($content){
        if ( file_exists($this->testPath))
            $this->error(sprintf('Same file exist in "%s"!' , $this->testPath ));
        if ( File::generate($this->testPath, $content) ) {
            $this->success(sprintf('Test created in "%s".' , $this->testPath ));
            $this->newLine();
            exit;
        }
    }

}