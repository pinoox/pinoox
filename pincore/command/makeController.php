<?php
namespace pinoox\command;


use pinoox\app\com_pinoox_manager\model\AppModel;
use pinoox\component\console;
use pinoox\component\Dir;
use pinoox\component\File;
use pinoox\component\HelperString;
use pinoox\component\interfaces\CommandInterface;


class makeController extends console implements CommandInterface
{

	/**
	* The console command name.
	*
	* @var string
	*/
	protected $signature = "app:make-controller";

	/**
	* The console command description.
	*
	* @var string
	*/
	protected $description = "Make controller in any application.";

	/**
	* The console command Arguments.
	*
	* @var array
	*/
	protected $arguments = [
		[ 'controller' , true , 'path and name of controller.'  ],
		[ 'package_name' , true , 'name of application.'  ],
	];

	/**
	* The console command Options.
	*
	* @var array
	*/
	protected $options = [
		[ 'extends' , 'e' , 'namespace of extends class' , 'MainController' ],
        [ "author" , "a" , "Code author, for copyright in source code." , 'Pinoox' ],
        [ "link" , "l" , "Author Connect Link, for copyright in source code." , 'https://www.pinoox.com/' ],
        [ "license" , null , "Put your license in source code (for example:`MIT`)." , null ],
        [ "pinlogo" , null , "if write this,pinoox logo into the in source code." , null ],
        [ "ignoreCopyright" , 'i' , "Don't show any copyright in source." , null ],
	];

	protected $nameSpaceOfControllerFolder = null;
	protected $nameSpaceOfController = null;
	protected $conteroller = null;
	protected $conterollerPath = null;
	protected $extend = null;
	protected $use = null;
	protected $package = null;
	/**
	* Execute the console command.
	*
	*/
	public function handle()
	{
        $this->package =  $this->argument('package_name');
        $app = AppModel::fetch_by_package_name($this->package);
        if ( is_null($app) )
            $this->error(sprintf('Can not find app with name `%s`!' , $this->package));

        $this->conterollerPath = Dir::path('~apps/' . $this->package.'/controller');

        $this->nameSpaceOfControllerFolder =  'pinoox\app\\'.$this->package.'\\controller';

        $controller = explode('\\' , str_replace('/' , '\\' , $this->argument('controller') ));
        $this->conteroller = array_pop($controller);
        $controllerScope = implode('\\' , $controller);
        $this->nameSpaceOfController = $this->nameSpaceOfControllerFolder . (( count($controller) > 0 ) ? '\\'.$controllerScope : "");

        $this->conterollerPath = $this->conterollerPath . ( ( count($controller) > 0 ) ? '/'.implode('/' , $controller) : "" ) . '/'.strtolower($this->conteroller) .'.controller.php';

        $extend = str_replace('/' , '\\' ,  $this->option('extends'));
        if ( HelperString::firstHas(strtolower($extend),'pinoox\\')){
            $extend =  explode('\\' ,$extend );
            $this->extend = end($extend);
            $this->use = implode('\\' , $extend);
        } elseif ( $extend == 'MainController' ){
            if ( $this->nameSpaceOfControllerFolder != $this->nameSpaceOfController ){
                $this->use = $this->nameSpaceOfControllerFolder.'\\MainController';
            }
            $this->extend = 'MainController';
        } elseif ( $this->option('extends') == null ){
            $this->use = null;
            $this->extend = null;
        } else {
            $extend =  explode('\\' ,$extend );
            $this->extend = end($extend);
            $controllerScope = implode('\\' , $controller);
            if ( $this->nameSpaceOfControllerFolder . ( ( count($extend) > 1 ) ? '\\'.$controllerScope : "" ) != $this->nameSpaceOfController )
                $this->use = $this->nameSpaceOfControllerFolder . ( ( count($extend) > 1 ) ? '\\'.$controllerScope : '\\'.$this->extend );
        }

        $this->makeController();

        $this->error(sprintf('Can not Create controller in "%s"!' , $this->conterollerPath ));
        $this->newLine();
        exit;
	}

    private function makeController()
    {
        $code = "<?php \n";
        $code .= $this->makeCopyWriteCode();
        $code .= $this->makeNameSpace();
        if ( $this->use != null )
            $code .= sprintf("use %s;\n\n", $this->use);
        $code .= sprintf("class %sController " , $this->conteroller );
        if ( $this->extend != null )
            $code .= sprintf("extends %s\n" , $this->extend );
        else
            $code .= "\n" ;
        $code .= "{\n\n";
        $code .= "\tpublic function change_me()\n";
        $code .= "\t{\n";
        $code .= "\t\t\n";
        $code .= "\t\t\n";
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
        return sprintf("namespace %s;\n\n",$this->nameSpaceOfController);
    }

    private function makeFile($content){
	    if ( file_exists($this->conterollerPath))
            $this->error(sprintf('Same file exist in "%s"!' , $this->conterollerPath ));
        if ( File::generate($this->conterollerPath, $content) ) {
            $this->success(sprintf('Controller created in "%s".' , $this->conterollerPath ));
            $this->newLine();
            exit;
        }
    }
}