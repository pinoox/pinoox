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

    protected $signature = 'app:make-command';

    protected $description = 'Create new command.';

    protected $arguments = [
        [ 'name' , true , 'Name of Command.' ],
        [ 'app_name' , true , 'Name of your application.' ],
    ];

    protected $options = [
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
            $this->success(sprintf('Command "%s" successfully generated in "%s"' , $this->name , $this->path));
        }
    }

    private function checkData(){
        if ( $this->argument('app_name') != '~'){
            if ( is_dir(Dir::path('~apps/'.$this->argument('app_name') )) ){
                $this->path = Dir::path('~apps/'.$this->argument('app_name').'/command/'.$this->argument('name').'.php' ) ;
                if ( ! is_file($this->path)){
                    $this->app = $this->argument('app_name') ;
                    $this->nameSpace = 'pinoox\app\\'.$this->argument('app_name') .'\command' ;
                    $this->name = $this->argument('name') ;
                } else {
                    $this->error(sprintf('Same command as name "%s" exist in "%s" application!' ,$this->argument('name') , $this->argument('app_name') ));
                }
            } else {
                $this->error(sprintf('Can not find "%s" application!' , $this->argument('app_name') ));
            }
        } else {
            $this->path = Dir::path('~pincore/command/'.$this->argument('name').'.php' ) ;
            if ( ! is_file($this->path)){
                $this->nameSpace = 'pinoox\command' ;
                $this->name = $this->argument('name') ;
            } else {
                $this->error(sprintf('Same command as name "%s" exist in Pincore!' ,$this->argument('name') ));
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
        $code .= sprintf("\t".'protected $signature = "%s";'."\n\n", $this->option('sign')) ;
        $code .= "\t/**\n\t* The console command description.\n\t*\n\t* @var string\n\t*/\n" ;
        $code .= sprintf("\t".'protected $description = "%s";'."\n\n", $this->option('description')) ;
        $code .= "\t/**\n\t* The console command Arguments.\n\t*\n\t* @var array\n\t*/\n" ;
        $code .= "\t".'protected $arguments = ['."\n" ;
        $code .= "\t\t".'//[ name , is_required , description , default ],'."\n\t];\n\n" ;
        $code .= "\t/**\n\t* The console command Options.\n\t*\n\t* @var array\n\t*/\n" ;
        $code .= "\t".'protected $options = ['."\n" ;
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