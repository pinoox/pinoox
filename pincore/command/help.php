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

    protected string $signature = 'help';

    protected string $description = 'Show list of all commands.';

    protected array $arguments = [
//      [ name , is_required , description , default ],
    ];

    protected array $options = [
//      [ name , short_name , description , default ],
        [ "command" , "c" , "Get help for special command." , null ],
        [ "version" , "v" , "Get version of pinoox." , null ],
    ];

    public function handle()
    {
        self::argument();
        if ( self::hasOption('version' , $this->options) ) {
            require_once (__DIR__.'/version.php');
            version::handleing();
            exit;
        }
        if ( ! self::option('command')) {
            $this->showAllCommand();
        } else {
            $this->showCommand(self::option('command'));
        }

    }

    private function showCommand($command){
        $commands = self::getListCommand($command);
        if ( $commands == false )
            self::error(sprintf('Command "%s" is not defined.', $command) );
        self::warning('Description:');
        self::newLine();
        self::info('  '.$commands["description"]);
        self::newLine();
        self::newLine();
        self::warning('Usage:');
        self::newLine();
        $text = '  '.$commands['signature'];
        if ( count($commands['arguments']) > 0 ){
            foreach ($commands['arguments'] as $arg ){
                $arg[0] = $arg[0] ?? $arg;
                if ( isset($arg[1]) and $arg[1] == false)
                    $text .= ' ['.$arg[0].']';
                else
                    $text .= ' <'.$arg[0].'>';
            }
        }
        $text .= ' [--Options]';
        self::info($text);
        if ( count($commands['arguments']) > 0 ){
            self::newLine();
            self::newLine();
            self::warning('Arguments:');
            self::newLine();
            $width = $this->getColumnWidthArguments(array_column($commands['arguments'] , 0));
            foreach ($commands['arguments'] as $arg ) {
                $arg[0] = $arg[0] ?? $arg;
                self::success('  ' . $arg[0]);
                self::info(str_repeat(" ", $width - HelperString::width($arg[0])) . ($arg[2] ?? ""));
                if ( isset($arg[3] ) and $arg[3] )
                    self::warning('  [ default: "'.$arg[3].'"]');
                self::newLine();
            }
        }
        $commands['Options'][] = ['help' , 'h' , 'Help Of Command.'];
        if ( count($commands['Options']) > 0 ){
            self::newLine();
            self::newLine();
            self::warning('Options:');
            self::newLine();
            $width = $this->getColumnWidthArguments(array_column($commands['Options'] , 1));
            $width2 = $this->getColumnWidthArguments(array_column($commands['Options'] , 0));
            foreach ($commands['Options'] as $arg ) {
                $arg[0] = $arg[0] ?? $arg;
                if ( isset($arg[1]) and $arg[1] != "" )
                    self::success('   --' . $arg[1].',  ');
                else
                    self::success(str_repeat(" ", $width  + 6));
                self::success('--'.$arg[0]);
                self::info(str_repeat(" ", $width2 - HelperString::width($arg[0]) + 2) . ($arg[2] ?? ""));
                if ( isset($arg[3]) and $arg[3] )
                    self::warning('  [ default: "'.$arg[3].'"]');
                self::newLine();
            }
        }
        self::newLine();
        self::newLine();
    }
    private function showAllCommand(){
        $commands = self::getListCommand();
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
        self::warning($lastCategory);
        self::newLine();
        $width = $this->getColumnWidth(array_merge($commands,$routeCommand));
        foreach ($routeCommand as $command ){
            self::success('  ' . $command['signature']);
            self::info(str_repeat(" ", $width - HelperString::width($command['signature']) ) . $command['description']);
            self::newLine();
        }
        $keys = array_column($commands, 'signature');
        array_multisort($keys, SORT_ASC, $commands);
        foreach ($commands as $index => $command ){
            $CommandGroup = explode(':',$command['signature'],2);
            if ( $CommandGroup[0] !=  $lastCategory){
                $lastCategory = $CommandGroup[0] ;
                self::warning($lastCategory);
                self::newLine();
            }
            self::success('  ' . $command['signature']);
            self::info(str_repeat(" ", $width - HelperString::width($command['signature']) ) . $command['description']);
            self::newLine();
        }
        self::newLine();
        self::newLine();
    }

    private function getColumnWidth($commands)
    {
        $widths = [];
        foreach ($commands as $command) {
            $widths[] = HelperString::width($command['signature']);
        }
        return $widths ? max($widths) + 2 : 0;
    }

    private function getColumnWidthArguments($commands)
    {
        $widths = [];
        foreach ($commands as $command) {
            $widths[] = HelperString::width($command);
        }
        return $widths ? max($widths) + 2 : 0;
    }
}