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
    private static $isHtml = false ;
    private static $CommandOptions = [] ;
    private static $CommandArguments = [] ;
    private static $CommandEnter = null ;
    private static $CommandClass = null ;
    private static $ProgressBar = [] ;

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


    /**
     * Run command from controller
     *
     * examples :
     * console::execute('make:build com_pinoox_welcome' , [],[] ,true) => return html output
     * console::execute('make:build com_pinoox_welcome' , [],[] ,false) => return console output
     * console::execute('make:app' , [],['h'] ,true)  => use empty option in option's array
     * console::execute('help' , [],['c'=>'make:app'] ,true) => use option with value in option's array
     * console::execute('make:app --h' , [],[] ,true)  => insert all data inside command parameter like shell
     * console::execute('help --c=make:app' , [],[] ,true)  => insert all data inside command parameter like shell
     * console::execute('help' , [],[] ,true , 'darkblue')  => change background color to dark blue.
     *
     * @param string $command Command signature or full command like in shell
     * @param array $arguments List of arguments in order of priority
     * @param array $options Array(key=>value) of options
     * @param bool $html Return text as html or console output
     * @param string $backGroundColor Color of background, if return as Html
     * @return false|string
     */
    public static function execute($command = 'help' , $arguments = [] , $options = [] , $html = true , $backGroundColor = "black" ){
        ob_start();
        $argv = ['pinoox' ] ;
        $argv = array_merge( $argv,  explode(' ',$command));
        if ( count($arguments ) > 0 )
            $argv = array_merge( $argv,  $arguments);
        if ( count($options ) > 0 )
            foreach ( $options as $key => $value )
                if ( is_int($key))
                    $argv[] = '--'.$value;
                else
                    $argv[] = '--'.$key.'='.$value;
        self::run($argv , true);
        $output = ob_get_contents();
        ob_end_clean();
        if ( $html ) {
            $output = '<div class="console"><pre>' . nl2br($output) . '</pre></div>';
            if ( ! is_null($backGroundColor) ){
                $output = '<div class="console" style="background-color:'.$backGroundColor.'; padding:5px">'.$output.'</div>';
            }
        }
        return $output;
    }

    public static function run($argv,$isHtml = false){
        self::$argument = $argv;
        self::$isHtml = $isHtml;
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
    protected static function command(){
        return self::$CommandEnter;
    }
    protected function hasOption($optionNeed,$Options){
        $OptionName = false;
        $OptionNameMin = false;
        foreach ($Options as $option ){
            if ( ( isset($option[0]) and $option[0] == $optionNeed ) or ( isset($option[1]) and $option[1] == $optionNeed ) ){
                $OptionName = isset($option[0]) ? '--'.$option[0] : false;
                $OptionNameMin = isset($option[1]) ? '--'.$option[1] : false;
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
        echo self::$isHtml ? "<br>":"\n";
    }
    protected function error($text){

        if ( self::$isHtml )
            echo '<div class="console"><pre>' ;
        $text = "     ".$text."     ";
        self::newLine();
        echo self::getColoredString(str_repeat(" ",strlen($text)) , 'white', 'red');
        self::newLine();
        echo self::getColoredString( $text  , 'white', 'red');
        self::newLine();
        echo self::getColoredString(str_repeat(" ",strlen($text)  ) , 'white', 'red');
        self::newLine();
        if ( self::$isHtml )
            echo '</pre></div>';
        exit;
    }

    protected function table($headers , $rows)
    {
        if ( ! self::$isHtml ) {
            try {
                self::newLine();
                $widths = [];
                foreach ($headers as $index => $header) {
                    $array = array_column($rows, $index);
                    $array[] = $header;
                    $widths[$index] = self::getColumnWidth($array) + 4;
                }
                $border = '┌';
                foreach ($widths as $width) {
                    $border .= str_repeat('─', $width);
                    if (next($widths) == true) {
                        $border .= '┬';
                    }
                }
                $border .= '┐';
                self::gray($border);
                self::newLine();
                self::gray('│');
                $border = '├';
                foreach ($headers as $index => $header) {
                    self::success(' ' . $header . str_repeat(' ', $widths[$index] - HelperString::width($header) - 1));
                    self::gray('│');
                    $border .= str_repeat('─', $widths[$index]);
                    if (next($headers) == true) {
                        $border .= '┼';
                    }
                }
                $border .= '┤';
                self::newLine();
                self::gray($border);

                foreach ($rows as $row) {
                    $hasNextRow = next($rows) == true;
                    self::newLine();
                    self::gray('│');
                    $border = $hasNextRow ? '├' : '└';
                    foreach ($row as $index => $column) {
                        self::info(' ' . $column . str_repeat(' ', (isset($widths[$index]) ? $widths[$index] : HelperString::width($column) + 1) - HelperString::width($column) - 1));
                        self::gray('│');
                        $border .= str_repeat('─', isset($widths[$index]) ? $widths[$index] : HelperString::width($column) + 1);
                        if (next($row) == true) {
                            $border .= $hasNextRow ? '┼' : '┴';
                        }
                    }
                    $border .= $hasNextRow ? '┤' : '┘';
                    self::newLine();
                    self::gray($border);
                }

                if (count($rows) == 0) {
                    $border = '└';
                    foreach ($widths as $width) {
                        $border .= str_repeat('─', $width);
                        if (next($widths) == true) {
                            $border .= '┴';
                        }
                    }
                    $border .= '┘';
                    self::newLine();
                    self::gray($border);
                }
                self::newLine();

            } catch (\Exception $e) {
                self::error('Table columns or rows is not match with each other!');
            }
        } else {
            echo '<table><tr><th>'.implode('</th><th>' ,$headers ).'</th></tr>';
            foreach ($rows as $row) {
                echo '<tr><td>'.implode('</td><td>' ,$row ).'</td></tr>';
            }
            echo '</table>';
        }
    }

    protected function startProgressBar($totalJob , $description = null)
    {
        self::$ProgressBar['totalJobs'] = $totalJob;
        self::$ProgressBar['completed'] = 0;
        self::$ProgressBar['description'] = $description;
        self::$ProgressBar['percent'] = floor(self::$ProgressBar['completed'] * 100 / $totalJob);
        self::$ProgressBar['pixel'] = floor(self::$ProgressBar['percent'] / 4);
        self::$ProgressBar['emptyPixel'] = 25 - self::$ProgressBar['pixel'] > 0 ? 25 - self::$ProgressBar['pixel'] : 0;
        if (!self::$isHtml) {
            self::info(sprintf("{%s%s} %s (%d/%d) \t%s\n", str_repeat('▓', self::$ProgressBar['pixel']), str_repeat('░', self::$ProgressBar['emptyPixel']), self::$ProgressBar['percent'] . '%', self::$ProgressBar['completed'], self::$ProgressBar['totalJobs'], self::$ProgressBar['description']));
        }
    }

    protected function nextStepProgressBar($jobs = 1 , $totalJobs = 0){
        self::$ProgressBar['completed'] = self::$ProgressBar['completed'] + $jobs;
        self::$ProgressBar['totalJobs'] = self::$ProgressBar['totalJobs'] + $totalJobs;
        if (self::$ProgressBar['completed'] > self::$ProgressBar['totalJobs'] ){
            self::$ProgressBar['totalJobs'] = self::$ProgressBar['completed'];
        }
        self::$ProgressBar['percent']  = floor(self::$ProgressBar['completed'] * 100 / self::$ProgressBar['totalJobs'] );
        self::$ProgressBar['pixel']  = floor(self::$ProgressBar['percent'] / 4);
        self::$ProgressBar['emptyPixel']  = 25 - self::$ProgressBar['pixel'] > 0 ? 25 - self::$ProgressBar['pixel'] : 0 ;
        if (!self::$isHtml) {
            self::moveUp();
            self::clearLine();
            self::info(sprintf("{%s%s} %s (%d/%d) \t%s\n", str_repeat('▓', self::$ProgressBar['pixel']), str_repeat('░', self::$ProgressBar['emptyPixel']), self::$ProgressBar['percent'] . '%', self::$ProgressBar['completed'], self::$ProgressBar['totalJobs'], self::$ProgressBar['description']));
        }
    }

    protected function finishProgressBar($description = ""){
        self::$ProgressBar['completed'] = self::$ProgressBar['totalJobs'];
        self::$ProgressBar['percent']  = 100;
        self::$ProgressBar['pixel']  = 25;
        self::$ProgressBar['emptyPixel']  =  0 ;
        if (!self::$isHtml) {
            self::moveUp();
            self::clearLine();
        }
        self::success(sprintf("{%s%s} %s (%d/%d) \t%s \t%s\n", str_repeat(self::$isHtml ? "■" : '▓', self::$ProgressBar['pixel']), str_repeat('░', self::$ProgressBar['emptyPixel']), self::$ProgressBar['percent'] . '%', self::$ProgressBar['completed'], self::$ProgressBar['totalJobs'], self::$ProgressBar['description'], $description));
        self::$ProgressBar = [];
    }


    protected function moveUp($lines = 1)
    {
        echo self::$isHtml ? "" : (sprintf("\x1b[%dA", $lines));
    }

    protected function moveDown($lines = 1)
    {
        echo self::$isHtml ? "" : (sprintf("\x1b[%dB", $lines));
    }

    protected function moveRight($columns = 1)
    {
        echo self::$isHtml ? "" : (sprintf("\x1b[%dC", $columns));
    }

    protected function moveLeft($columns = 1)
    {
        echo self::$isHtml ? "" : (sprintf("\x1b[%dD", $columns));
    }

    protected function moveToColumn($column)
    {
        echo self::$isHtml ? "" : (sprintf("\x1b[%dG", $column));
    }

    protected function moveToPosition($column, $row)
    {
        echo self::$isHtml ? "" : (sprintf("\x1b[%d;%dH", $row + 1, $column));
    }


    /**
     * Clears all the output from the current line.
     */
    protected function clearLine()
    {
        echo self::$isHtml ? "" : ("\x1b[2K");
    }

    /**
     * Clears all the output from the current line after the current position.
     */
    protected function clearLineAfter()
    {
        echo self::$isHtml ? "" : ("\x1b[K");
    }

    /**
     * Clears all the output from the cursors' current position to the end of the screen.
     */
    protected function clearOutput()
    {
        echo self::$isHtml ? "" : ("\x1b[0J");
    }

    /**
     * Clears the entire screen.
     */
    protected function clearScreen()
    {
        echo self::$isHtml ? "" : ("\x1b[2J");
    }

    /**
     * Returns the current cursor position as x,y coordinates.
     */
    protected function getCurrentPosition()
    {
        static $isTtySupported;

        if (null === $isTtySupported && \function_exists('proc_open')) {
            $isTtySupported = (bool) @proc_open('echo 1 >/dev/null', [['file', '/dev/tty', 'r'], ['file', '/dev/tty', 'w'], ['file', '/dev/tty', 'w']], $pipes);
        }

        if (!$isTtySupported) {
            return [1, 1];
        }

        $sttyMode = shell_exec('stty -g');
        shell_exec('stty -icanon -echo');

        @fwrite($this->input, "\033[6n");

        $code = trim(fread($this->input, 1024));

        shell_exec(sprintf('stty %s', $sttyMode));

        sscanf($code, "\033[%d;%dR", $row, $col);

        return [$col, $row];
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
        self::$CommandSignature = isset($arguments[1]) ? $arguments[1] : "help";
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
                        'description' => isset($description) ? $description : "",
                        'arguments' => isset($arguments) ? $arguments : [],
                        'Options' => isset($Options) ? $Options : [],
                        'class' => $class
                    ];
                    if ( ! is_null($needCommand) and $signature == $needCommand)
                        break;
                } catch (\ReflectionException $e) {
                }
            }
        }
        return is_null($needCommand) ? $result : ( isset($result[$needCommand]) ? $result[$needCommand] : false);
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
                $CommandArguments[$argument[0]] = isset($TempCommandArguments[$index]) ?
                                                    $TempCommandArguments[$index] :
                                                    ( isset($argument[3]) ? $argument[3] : null );
        }
        if ( count($errors)  > 0 ){
            self::error(sprintf("Not enough arguments (missing: %s)." , implode(", " , $errors)) );
        }
        foreach ( $command['Options'] as $index => $Option){
            if ( ! is_array($Option) )
                $Option[0] = $Option;
            $CommandOptions[$Option[0]] = isset($TempCommandOptions[$Option[0]]) ? $TempCommandOptions[$Option[0]] : ( isset($Option[3]) ? $Option[3] : null );
            if ( isset($Option[1])){
                $CommandOptions[$Option[0]] = isset($TempCommandOptions[$Option[1]]) ? $TempCommandOptions[$Option[1]] : ( isset($Option[3]) ? $Option[3] : null );
                $CommandOptions[$Option[1]] = isset($TempCommandOptions[$Option[1]]) ? $TempCommandOptions[$Option[1]] : ( isset($Option[3]) ? $Option[3] : null );
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

        if ( self::$isHtml ){
            $colored_string .= "<span style=\"";

            if (! is_null($foreground_color) )
                $colored_string .= "color:".$foreground_color.";";
            if (! is_null($background_color) )
                $colored_string .= "background-color:".$background_color.";" ;
            $colored_string .= "\">".$string."</span>";

        } else {
            // Check if given foreground color found
            if ( isset(self::$foreground_colors[$foreground_color]) ) {
                $colored_string .= "\033["  . self::$foreground_colors[$foreground_color] . "m" ;
            }
            // Check if given background color found
            if ( isset(self::$background_colors[$background_color]) ) {
                $colored_string .=  "\033[" . self::$background_colors[$background_color] . "m" ;
            }

            // Add string and end coloring
            $colored_string .=  $string . "\033[0m";
        }
        return $colored_string;
    }
}