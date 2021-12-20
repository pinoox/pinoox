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
use pinoox\component\HelperString;
use pinoox\component\interfaces\CommandInterface;

class help extends console implements CommandInterface
{

    protected $signature = 'help';

    protected $description = 'Show list of all commands.';

    protected $arguments = [
//      [ name , is_required , description , default ],
    ];

    protected $options = [
//      [ name , short_name , description , default ],
        [ "command" , "c" , "Get help for special command." , null ],
        [ "version" , "v" , "Get version of pinoox." , null ],
    ];

    public function handle()
    {
        $this->argument();
        if ( $this->hasOption('version' , $this->options) ) {
            require_once (__DIR__.'/version.php');
            version::handleing();
            exit;
        }
        if ( ! $this->option('command')) {
            $this->showAllCommand();
        } else {
            $this->showCommand($this->option('command'));
        }

    }

    private function showCommand($command){
        $commands = self::getListCommand($command);
        if ( $commands == false )
            $this->error(sprintf('Command "%s" is not defined.', $command) );
        $this->warning('Description:');
        $this->newLine();
        $this->info('  '.$commands["description"]);
        $this->newLine();
        $this->newLine();
        $this->warning('Usage:');
        $this->newLine();
        $text = '  '.$commands['signature'];
        if ( count($commands['arguments']) > 0 ){
            foreach ($commands['arguments'] as $arg ){
                $arg[0] = isset($arg[0]) ? $arg[0] : $arg;
                if ( isset($arg[1]) and $arg[1] == false)
                    $text .= ' ['.$arg[0].']';
                else
                    $text .= ' <'.$arg[0].'>';
            }
        }
        $text .= ' [--Options]';
        $this->info($text);
        if ( count($commands['arguments']) > 0 ){
            $this->newLine();
            $this->newLine();
            $this->warning('Arguments:');
            $this->newLine();
            $width = $this->getColumnWidth(array_column($commands['arguments'] , 0));
            foreach ($commands['arguments'] as $arg ) {
                $arg[0] = isset($arg[0]) ? $arg[0] : $arg;
                $this->success('  ' . $arg[0]);
                $this->info(str_repeat(" ", $width - HelperString::width($arg[0])) . (isset($arg[2]) ? $arg[2] : ""));
                if ( isset($arg[3] ) and $arg[3] )
                    $this->warning('  [ default: "'.$arg[3].'"]');
                $this->newLine();
            }
        }
        $commands['Options'][] = ['help' , 'h' , 'Help Of Command.'];
        if ( count($commands['Options']) > 0 ){
            $this->newLine();
            $this->newLine();
            $this->warning('Options:');
            $this->newLine();
            $width = $this->getColumnWidth(array_column($commands['Options'] , '1'));
            $width2 = $this->getColumnWidth(array_column($commands['Options'] , '0'));
            foreach ($commands['Options'] as $arg ) {
                $arg[0] = isset($arg[0]) ? $arg[0] : $arg;
                if ( isset($arg[1]) and $arg[1] != "" )
                    $this->success(str_repeat(" ", $width  - HelperString::width($arg[1]) ) .'--' . $arg[1].', ' );
                else
                    $this->success(str_repeat(" ", $width  + 4));
                $this->success( '--'.$arg[0]);
                $this->info(str_repeat(" ", $width2 - HelperString::width($arg[0]) + 2) . (isset($arg[2]) ? $arg[2] : ""));
                if ( isset($arg[3]) and $arg[3] )
                    $this->warning('  [ default: "'.$arg[3].'"]');
                $this->newLine();
            }
        }
        $this->newLine();
        $this->newLine();
    }
    private function showAllCommand(){
        $commands = $this->getListCommand();
        $routeCommand = [];
        foreach ($commands as $index => $command ){
            if ( ! HelperString::has( $command['signature'] , ':')){
                $routeCommand[] = $command;
                unset($commands[$index]);
            }
        }
        $lastCategory = "Root";
        $keys = array_column($routeCommand, 'signature');
        array_multisort($keys, SORT_ASC, $routeCommand);
        $this->warning($lastCategory);
        $this->newLine();
        $width = $this->getColumnWidth(array_column(array_merge($commands,$routeCommand) , 'signature' ));
        foreach ($routeCommand as $command ){
            $this->success('  ' . $command['signature']);
            $this->info(str_repeat(" ", $width - HelperString::width($command['signature']) ) . $command['description']);
            $this->newLine();
        }
        $keys = array_column($commands, 'signature');
        array_multisort($keys, SORT_ASC, $commands);
        foreach ($commands as $index => $command ){
            $CommandGroup = explode(':',$command['signature'],2);
            if ( $CommandGroup[0] !=  $lastCategory){
                $lastCategory = $CommandGroup[0] ;
                $this->warning($lastCategory);
                $this->newLine();
            }
            $this->success('  ' . $command['signature']);
            $this->info(str_repeat(" ", $width - HelperString::width($command['signature']) ) . $command['description']);
            $this->newLine();
        }
        $this->newLine();
        $this->newLine();
    }

}