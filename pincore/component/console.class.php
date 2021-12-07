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


namespace pinoox\component;


use pinoox\component\app\AppProvider;
use ReflectionClass;

class console
{
    private static $argument = [] ;
    private static $CommandSignature = null ;
    private static $CommandOptions = [] ;
    private static $CommandArguments = [] ;
    private static $CommandEnter = null ;
    private static $CommandClass = null ;

    private static $foreground_colors = array(
        'black'        => '0;30', 'dark_gray'    => '1;30',
        'blue'         => '0;34', 'light_blue'   => '1;34',
        'green'        => '0;32', 'light_green'  => '1;32',
        'cyan'         => '0;36', 'light_cyan'   => '1;36',
        'red'          => '0;31', 'light_red'    => '1;31',
        'purple'       => '0;35', 'light_purple' => '1;35',
        'brown'        => '0;33', 'yellow'   => '1;33',
        'light_gray'   => '0;37', 'white'        => '1;37',
    );
    private static $background_colors = array(
        'black'        => '40', 'red'          => '41',
        'green'        => '42', 'yellow'       => '43',
        'blue'         => '44', 'magenta'      => '45',
        'cyan'         => '46', 'light_gray'   => '47',
    );


    public static function run($argv){
        self::$argument = $argv;
        self::findCommand();
    }

    protected function option($key = null ){
        if ( is_null($key) )
            return self::$CommandOptions;

        if ( isset(self::$CommandOptions[$key]) )
            return self::$CommandOptions[$key];

        return false;
    }
    protected function argument($key = null ){
        if ( is_null($key) )
            return self::$CommandArguments;

        if ( isset(self::$CommandArguments[$key]) )
            return self::$CommandArguments[$key];

        return false;
    }
    public static function command(){
        return self::$CommandEnter;
    }
    protected function hasOption($optionNeed,$Options){
        $OptionName = false;
        $OptionNameMin = false;
        foreach ($Options as $option ){
            if ( ( isset($option[0]) and $option[0] == $optionNeed ) or ( isset($option[1]) and $option[1] == $optionNeed ) ){
                $OptionName = '--'.$option[0] ?? false;
                $OptionNameMin = '--'.$option[1] ?? false;
            }
        }
        return
            ( $OptionName != false and (
                HelperString::has(self::command() , $OptionName.' ')
                or HelperString::has(self::command() , $OptionName.'=')
                or HelperString::lastHas(self::command() , $OptionName) ))
            or
            ( $OptionNameMin != false and (
                    HelperString::has(self::command() , $OptionNameMin.' ')
                    or HelperString::has(self::command() , $OptionNameMin.'=')
                    or HelperString::lastHas(self::command() , $OptionNameMin) )) ;
    }


    protected function success($text){
        echo self::getColoredString($text , 'green');
    }
    protected function danger($text){
        echo self::getColoredString($text , 'red');
    }
    protected function warning($text){
        echo self::getColoredString($text , 'yellow');
    }
    protected function info($text){
        echo self::getColoredString($text , 'white');
    }
    protected function gray($text){
        echo self::getColoredString($text , 'dark_gray');
    }
    protected function newLine(){
        echo "\n";
    }
    protected function error($text){
        $text = "     ".$text."     ";
        self::newLine();
        echo self::getColoredString(str_repeat(" ",strlen($text)) , 'white', 'red');
        self::newLine();
        echo self::getColoredString( $text  , 'white', 'red');
        self::newLine();
        echo self::getColoredString(str_repeat(" ",strlen($text)  ) , 'white', 'red');
        self::newLine();
        exit;
    }

    protected function table($headers , $rows)
    {
        try {

            $widths = [];
            foreach ($headers as $index => $header) {
                $array = array_column($rows, $index);
                $array[]= $header;
                $widths[$index] = self::getColumnWidth($array) + 4;
            }
            $border = '┌';
            foreach ($widths as $width){
                $border .= str_repeat('─' , $width);
                if (next($widths)==true){
                    $border .= '┬';
                }
            }
            $border .= '┐';
            self::gray($border);
            self::newLine();
            self::gray('│');
            $border = '├';
            foreach ($headers as $index => $header) {
                self::success(' '.$header . str_repeat(' ',$widths[$index] - HelperString::width($header) - 1) );
                self::gray('│');
                $border .= str_repeat('─' , $widths[$index]);
                if (next($headers)==true){
                    $border .= '┼';
                }
            }
            $border .= '┤';
            self::newLine();
            self::gray($border);

            foreach ($rows  as $row){
                $hasNextRow = next($rows) == true;
                self::newLine();
                self::gray('│');
                $border = $hasNextRow ? '├' : '└';
                foreach ($row as $index => $column){
                    self::info(' '.$column . str_repeat(' ',($widths[$index] ?? HelperString::width($column) + 1) - HelperString::width($column) - 1) );
                    self::gray('│');
                    $border .= str_repeat('─' , $widths[$index] ?? HelperString::width($column) + 1 );
                    if (next($row)==true){
                        $border .= $hasNextRow ? '┼' : '┴';
                    }
                }
                $border .= $hasNextRow  ? '┤' :'┘';
                self::newLine();
                self::gray($border);
            }

            if ( count($rows) == 0 ){
                $border = '└';
                foreach ($widths as $width){
                    $border .= str_repeat('─' , $width);
                    if (next($widths)==true){
                        $border .= '┴';
                    }
                }
                $border .= '┘';
                self::newLine();
                self::gray($border);
            }

        } catch (\Exception $e){
            self::error('Table columns or rows is not match with each other!');
        }
    }

    protected function getColumnWidth($commands)
    {
        $widths = [];
        foreach ($commands as $command) {
            $widths[] = HelperString::width($command);
        }
        return $widths ? max($widths) + 2 : 0;
    }
    private static function findCommand(){
        $arguments = self::$argument;
        if ( isset($arguments[1]) and HelperString::has($arguments[1], '--')){
            array_splice( $arguments, 1, 0, ['help'] );
        }
        if ( in_array('--h' , $arguments ) or in_array('--help' , $arguments ) ){
            self::$argument[] = '--c='.$arguments[1];
            array_splice( $arguments, 1, 1, ['help'] );
        }
        self::$CommandSignature = $arguments[1] ?? "help";
        $command = self::getListCommand( self::$CommandSignature );
        if ( $command == false ){
            self::error(sprintf('Command "%s" is not defined.', self::$CommandSignature) );
        }
        self::parseCommand($command);
        call_user_func_array([$command['class'], "handle"],[]);
    }

    protected static function getListCommand( $needCommand = null){
        $commands = self::getListCommandFile();
        $result = [];
        foreach ( $commands as $command ){
            require_once($command['file']);
            if (class_exists($command['class']) and method_exists($command['class'], 'handle')) {
                try {
                    $class = new $command['class']();
                    $reflection = new ReflectionClass($class);
                    $property = $reflection->getProperty('signature');
                    $property->setAccessible(true);
                    $signature = $property->getValue($class) ;
                    if ( $reflection->hasProperty('description')) {
                        $property = $reflection->getProperty('description');
                        $property->setAccessible(true);
                        $description = $property->getValue($class);
                    }
                    if ( $reflection->hasProperty('arguments')) {
                        $property = $reflection->getProperty('arguments');
                        $property->setAccessible(true);
                        $arguments = $property->getValue($class) ;
                    }
                    if ( $reflection->hasProperty('options')) {
                        $property = $reflection->getProperty('options');
                        $property->setAccessible(true);
                        $Options = $property->getValue($class) ;
                    }
                    $result[$signature] = [
                        'signature' => $signature,
                        'description' => $description ?? "",
                        'arguments' => $arguments ?? [],
                        'Options' => $Options ?? [],
                        'class' => $class
                    ];
                    if ( ! is_null($needCommand) and $signature == $needCommand)
                        break;
                } catch (\ReflectionException $e) {
                }
            }
        }
        return is_null($needCommand) ? $result : ($result[$needCommand] ?? false);
    }

    private static function parseCommand($command){
        $arguments = self::$argument;
        unset($arguments[0],$arguments[1]);
        $CommandArguments = [];
        $CommandOptions = [];
        $TempCommandArguments = [];
        $TempCommandOptions = [];
        foreach ($arguments as $argument){
            if ( HelperString::firstHas($argument, '--')){
                $argument = HelperString::firstDelete($argument, '--');
                if ( HelperString::has($argument , '='))
                    list($key,$value) = explode('=' , $argument , 2);
                else
                    list($key,$value) = [$argument , null];
                $TempCommandOptions[$key] = $value;
            } else {
                $TempCommandArguments[] = $argument;
            }
        }
        $errors = [];
        foreach ( $command['arguments'] as $index => $argument){
            if ( ! is_array($argument) )
                $argument[0] = $argument;
            if ( isset( $argument[1] ) and $argument[1] and ! isset($TempCommandArguments[$index]) )
                $errors[] = sprintf('"%s"' , $argument[0] );
            else
                $CommandArguments[$argument[0]] = $TempCommandArguments[$index] ?? $argument[3] ?? null;
        }
        if ( count($errors)  > 0 ){
            self::error(sprintf("Not enough arguments (missing: %s)." , implode(", " , $errors)) );
        }
        foreach ( $command['Options'] as $index => $Option){
            if ( ! is_array($Option) )
                $Option[0] = $Option;
            $CommandOptions[$Option[0]] = $TempCommandOptions[$Option[0]] ?? $Option[3] ?? null;
            if ( isset($Option[1])){
                $CommandOptions[$Option[0]] = $TempCommandOptions[$Option[1]] ?? $Option[3] ?? null;
                $CommandOptions[$Option[1]] = $TempCommandOptions[$Option[1]] ?? $Option[3] ?? null;
            }
        }
        call_user_func_array([$command['class'], "setCommandData"],[$CommandArguments , $CommandOptions , implode(' ' ,self::$argument)]);
    }

    public static function setCommandData($arguments , $options , $Command){
        self::$CommandArguments = $arguments;
        self::$CommandOptions = $options;
        self::$CommandEnter = $Command;
    }

    private static function getListCommandFile(){
        $path = Dir::path('~apps/');
        $folders = File::get_dir_folders($path);
        $result = [];
        AppProvider::bake('~');
        foreach ($folders as $folder) {
            $package_key = basename($folder);
            AppProvider::app($package_key);
            $isEnable = AppProvider::get('enable');
            if (!$isEnable)
                continue;

            $commandPath = $folder.'command'.DIRECTORY_SEPARATOR;
            if ( ! is_dir($commandPath) )
                continue;
            $files = File::get_files_by_pattern($commandPath , '*.php');
            foreach ( $files as $file) {
                $result[] = [
                    'package_name' => $package_key,
                    'class' => 'pinoox\app\\'.$package_key.'\command\\'.file::name($file),
                    'file' => $file
                ];
            }
        }
        $commandPath = Dir::path('~pincore/command/');
        $files = File::get_files_by_pattern($commandPath , '*.php');
        foreach ( $files as $file) {
            $result[] = [
                'package_name' => '~',
                'class' => 'pinoox\command\\'.file::name($file),
                'file' => $file
            ];
        }
        AppProvider::app('~');
        return $result;
    }


    // Returns colored string
    protected function getColoredString($string, $foreground_color = null, $background_color = null) {
        $colored_string = "";

        // Check if given foreground color found
        if ( isset(self::$foreground_colors[$foreground_color]) ) {
            $colored_string .= "\033[" . self::$foreground_colors[$foreground_color] . "m";
        }
        // Check if given background color found
        if ( isset(self::$background_colors[$background_color]) ) {
            $colored_string .= "\033[" . self::$background_colors[$background_color] . "m";
        }

        // Add string and end coloring
        $colored_string .=  $string . "\033[0m";

        return $colored_string;
    }
}