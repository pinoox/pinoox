<?php
/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Erfan Ebrahimi
 * @link http://www.erfanebrahimi.ir/
 * @license  https://opensource.org/licenses/MIT MIT License
 */
namespace pinoox\command;

use pinoox\component\console;
use pinoox\component\Dir;
use pinoox\component\File;
use pinoox\component\HelperString;
use pinoox\component\interfaces\CommandInterface;

class makeCommand extends console implements CommandInterface
{

    protected string $signature = 'make:command';

    protected string $description = 'Create new command.';

    protected array $arguments = [
        [ 'name' , true , 'Name of Command.' ],
        [ 'app_name' , true , 'Name of your application.' ],
    ];

    protected array $options = [
        [ "sign" , "s" , "Signature of call command." , 'new:command' ],
        [ "description" , "d" , "Set Description of this command." , "Description of this command" ],
    ];

    protected $app = null;
    protected $name = null;
    protected $nameSpace = null;
    protected $path = null;
    public function handle()
    {
        $this->checkData();
        if ( $this->writeCode() ) {
            self::success(sprintf('Command "%s" successfully generated in "%s"' , $this->name , $this->path));
        }
    }

    private function checkData(){
        if ( self::argument('app_name') != '~'){
            if ( is_dir(Dir::path('~apps/'.self::argument('app_name') )) ){
                $this->path = Dir::path('~apps/'.self::argument('app_name').'/command/'.self::argument('name').'.php' ) ;
                if ( ! is_file($this->path)){
                    $this->app = self::argument('app_name') ;
                    $this->nameSpace = 'pinoox\app\\'.self::argument('app_name') .'\command' ;
                    $this->name = self::argument('name') ;
                } else {
                    self::error(sprintf('Same command as name "%s" exist in "%s" application!' ,self::argument('name') , self::argument('app_name') ));
                }
            } else {
                self::error(sprintf('Can not find "%s" application!' , self::argument('app_name') ));
            }
        } else {
            $this->path = Dir::path('~pincore/command/'.self::argument('name').'.php' ) ;
            if ( ! is_file($this->path)){
                $this->nameSpace = 'pinoox\command' ;
                $this->name = self::argument('name') ;
            } else {
                self::error(sprintf('Same command as name "%s" exist in Pincore!' ,self::argument('name') ));
            }
        }
    }
    private function getPhpCode(){
        $code = "<?php\n";
        $code .= sprintf("namespace %s;\n\n\n" , $this->nameSpace);
        $code .= "use pinoox\component\console;\n" ;
        $code .= "use pinoox\component\interfaces\CommandInterface;\n\n\n" ;
        $code .= sprintf("class %s extends console implements CommandInterface\n{\n\n", $this->name) ;
        $code .= "\t/**\n\t* The console command name.\n\t*\n\t* @var string\n\t*/\n" ;
        $code .= sprintf("\t".'protected string $signature = "%s";'."\n\n", self::option('sign')) ;
        $code .= "\t/**\n\t* The console command description.\n\t*\n\t* @var string\n\t*/\n" ;
        $code .= sprintf("\t".'protected string $description = "%s";'."\n\n", self::option('description')) ;
        $code .= "\t/**\n\t* The console command Arguments.\n\t*\n\t* @var array\n\t*/\n" ;
        $code .= "\t".'protected array $arguments = ['."\n" ;
        $code .= "\t\t".'//[ name , is_required , description , default ],'."\n\t];\n\n" ;
        $code .= "\t/**\n\t* The console command Options.\n\t*\n\t* @var array\n\t*/\n" ;
        $code .= "\t".'protected array $options = ['."\n" ;
        $code .= "\t\t".'//[ name , short_name , description , default ],'."\n\t];\n\n" ;
        $code .= "\t/**\n\t* Execute the console command.\n\t*\n\t*/\n" ;
        $code .= "\t".'public function handle()'."\n\t{\n\t\t// TODO: Implement handle() method.\n\t}\n" ;
        $code .= "\n}" ;
        return $code;
    }

    private function writeCode()
    {
        return File::generate($this->path , $this->getPhpCode());
    }
}