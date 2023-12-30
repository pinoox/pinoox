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


namespace Pinoox\Component;

use Pinoox\Component\Helpers\HelperString;
use Pinoox\Component\Package\AppBuilder;
use ReflectionClass;
use Pinoox\Portal\Finder;
use Symfony\Component\Finder\SplFileInfo;

class Console
{
    private static $CommandOptions = [];
    private static $argument = [];
    private static $CommandSignature = null;
    private static $isHtml = false;
    private static $CommandArguments = [];
    private static $CommandEnter = null;
    private static $CommandClass = null;
    private static $ProgressBar = [];

    private static $foreground_colors = array(
        'black' => '0;30', 'dark_gray' => '1;30',
        'blue' => '0;34', 'light_blue' => '1;34',
        'green' => '0;32', 'light_green' => '1;32',
        'cyan' => '0;36', 'light_cyan' => '1;36',
        'red' => '0;31', 'light_red' => '1;31',
        'purple' => '0;35', 'light_purple' => '1;35',
        'brown' => '0;33', 'yellow' => '1;33',
        'light_gray' => '0;37', 'white' => '1;1',
    );
    private static $background_colors = array(
        'black' => '40', 'red' => '41',
        'green' => '42', 'yellow' => '43',
        'blue' => '44', 'magenta' => '45',
        'cyan' => '46', 'light_gray' => '47',
    );
    protected $cli;


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
    public static function execute($command = 'help', $arguments = [], $options = [], $html = true, $backGroundColor = "black")
    {
        ob_start();
        $argv = ['pinoox'];
        $argv = array_merge($argv, explode(' ', $command));
        if (count($arguments) > 0)
            $argv = array_merge($argv, $arguments);
        if (count($options) > 0)
            foreach ($options as $key => $value)
                if (is_int($key))
                    $argv[] = '--' . $value;
                else
                    $argv[] = '--' . $key . '=' . $value;

        self::run($argv, $html);

        $output = ob_get_contents();
        ob_end_clean();
        if ($html) {
            $output = '<div class="console"><pre>' . nl2br($output) . '</pre></div>';
            if (!is_null($backGroundColor)) {
                $output = '<div class="console" style="background-color:' . $backGroundColor . '; padding:5px">' . $output . '</div>';
            }
        }
        return $output;
    }

    public static function run($argv, $isHtml = false)
    {
        self::$argument = $argv;
        self::$isHtml = $isHtml;
        self::findCommand();
    }

    protected static function option($key = null)
    {
        if (is_null($key))
            return self::$CommandOptions;

        if (isset(self::$CommandOptions[$key]))
            return self::$CommandOptions[$key];

        return false;
    }

    protected static function argument($key = null)
    {
        if (is_null($key))
            return self::$CommandArguments;

        if (isset(self::$CommandArguments[$key]))
            return self::$CommandArguments[$key];

        return false;
    }

    protected static function command()
    {
        return self::$CommandEnter;
    }

    protected static function hasOption($optionNeed, $Options): bool
    {
        $OptionName = false;
        $OptionNameMin = false;
        foreach ($Options as $option) {
            if ((isset($option[0]) and $option[0] == $optionNeed) or (isset($option[1]) and $option[1] == $optionNeed)) {

                $OptionName = isset($option[0]) ? '--' . $option[0] : false;
                $OptionNameMin = isset($option[1]) ? '--' . $option[1] : false;
            }
        }
        return
            ($OptionName != false and (
                    HelperString::has(self::command(), $OptionName . ' ')
                    or HelperString::has(self::command(), $OptionName . '=')
                    or HelperString::lastHas(self::command(), $OptionName)))
            or
            ($OptionNameMin != false and (
                    HelperString::has(self::command(), $OptionNameMin . ' ')
                    or HelperString::has(self::command(), $OptionNameMin . '=')
                    or HelperString::lastHas(self::command(), $OptionNameMin)));
    }

    protected static function success($text)
    {
        echo self::getColoredString($text, 'green');
    }

    protected static function white($text)
    {
        echo self::getColoredString($text, 'white');
    }

    protected static function danger($text)
    {
        echo self::getColoredString($text, 'red');
    }

    protected static function warning($text)
    {
        echo self::getColoredString($text, 'yellow');
    }

    protected static function info($text)
    {
        echo self::getColoredString($text, 'white');
    }

    protected static function gray($text)
    {
        echo self::getColoredString($text, 'dark_gray');
    }

    protected static function newLine()
    {
        echo self::$isHtml ? "<br>" : "\n";
    }

    protected static function error($text, $exit = true)
    {
        if (self::$isHtml)
            echo '<div class="console"><pre>';
        $text = "     " . $text . "     ";
        self::newLine();
        echo self::getColoredString(str_repeat(" ", strlen($text)), 'white', 'red');
        self::newLine();
        echo self::getColoredString($text, 'white', 'red');
        self::newLine();
        echo self::getColoredString(str_repeat(" ", strlen($text)), 'white', 'red');
        self::newLine();
        if (self::$isHtml)
            echo '</pre></div>';
        if ($exit)
            exit;
    }

    /**
     * choice from selected option
     * @param string $question
     * @param array $choices
     * @param null $default
     * @param int $attempts
     * @param false $multiple
     * @return string|int|array
     */
    protected static function choice($question, $choices, $default = null, $multiple = false, $attempts = 2)
    {
        self::success($question);
        self::newLine();
        $choiceIndex = 1;
        if (is_array($choices)) {
            foreach ($choices as $choice) {
                self::gray("  [");
                self::warning($choiceIndex);
                self::gray("] : ");
                self::info($choice);
                self::newLine();
                $choiceIndex++;
            }
            $choiceIndex--;
        }
        if (self::$isHtml)
            return $default;
        $numberOfTry = 0;
        $keys = array_keys($choices);
        $resultMultiChoice = [];
        while (($numberOfTry < $attempts and !$multiple) or ($multiple)) {
            $hasError = false;
            $selectChoiceStr = trim(self::input());
            $selectChoice = intval($selectChoiceStr);
            if ($selectChoice >= 1 and $selectChoice <= $choiceIndex and isset($keys[$selectChoice - 1])) {
                if (!$multiple) {
                    self::success(sprintf('You select `%s`', $choices[$keys[$selectChoice - 1]]));
                    self::newLine();
                    return $keys[$selectChoice - 1];
                } else {
                    $resultMultiChoice[] = $keys[$selectChoice - 1];
                    $resultMultiChoice = array_unique($resultMultiChoice);
                    foreach ($resultMultiChoice as $oneOfThem) {
                        self::success(sprintf('-You select `%s`', $choices[$oneOfThem]));
                        self::newLine();
                    }
                    self::info('Peals enter next option.( if you want to finish selection, press [');
                    self::warning('0');
                    self::info('] )');
                    self::newLine();
                }
            } elseif ($multiple and trim($selectChoiceStr) == "0") {
                self::newLine();
                if (count($resultMultiChoice) == 0)
                    return $default;
                return $resultMultiChoice;
            } else
                $hasError = true;
            if (!$multiple)
                $numberOfTry++;
            if ($numberOfTry < $attempts and (!$multiple or $hasError)) {
                self::info('Please just enter `');
                self::warning('Yellow number');
                self::info('` of row you want to select! (');
                self::warning('1');
                self::info('-');
                self::warning($choiceIndex);
                self::info(')');
                self::newLine();
            }
        }
        return $default;
    }

    /**
     * confirm operation
     * @param string $operation
     * @return boolean
     */
    protected static function confirm($operation)
    {
        self::error(sprintf('%s (y|n)', $operation), false);
        while (true) {
            $enterStr = trim(self::input(1));
            if ($enterStr == "Y" or $enterStr == "1" or $enterStr == "y" or $enterStr == "t" or $enterStr == "T") {
                return true;
            } elseif ($enterStr == "N" or $enterStr == "0" or $enterStr == "n" or $enterStr == "f" or $enterStr == "F") {
                return false;
            }
            self::info('Please just enter `');
            self::warning('y');
            self::info('` or `');
            self::warning('n');
            self::info('`');
            self::newLine();
        }
    }

    /**
     * get string from user
     * @param int $len
     * @return string
     */
    protected static function input($len = -1)
    {
        if (intval($len) >= 0) {
            $resSTDIN = fopen("php://stdin", "r");
            $enterStr = stream_get_contents($resSTDIN, intval($len));
            fclose($resSTDIN);
        } else
            $enterStr = readline();
        return $enterStr;
    }

    /**
     * @param string $prompt
     * @return string|void
     */
    protected static function input_dialog($prompt = "Enter Password:")
    {
        if (preg_match('/^win/i', PHP_OS)) {
            $vbscript = sys_get_temp_dir() . 'prompt_password.vbs';
            file_put_contents(
                $vbscript, 'wscript.echo(InputBox("'
                . addslashes($prompt)
                . '", "", "password here"))');
            $command = "cscript //nologo " . escapeshellarg($vbscript);
            $password = rtrim(shell_exec($command));
            unlink($vbscript);
            return $password;
        } else {
            $command = "/usr/bin/env bash -c 'echo OK'";
            if (rtrim(shell_exec($command)) !== 'OK') {
                trigger_error("Can't invoke bash");
                return;
            }
            $command = "/usr/bin/env bash -c 'read -s -p \""
                . addslashes($prompt)
                . "\" mypassword && echo \$mypassword'";
            $password = rtrim(shell_exec($command));
            echo "\n";
            return $password;
        }
    }

    protected static function table($headers, $rows)
    {
        if (!self::$isHtml) {
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
                self::white($border);
                self::newLine();
                self::white('│');
                $border = '├';
                foreach ($headers as $index => $header) {
                    self::white(' ' . $header . str_repeat(' ', $widths[$index] - HelperString::width($header) - 1));
                    self::white('│');
                    $border .= str_repeat('─', $widths[$index]);
                    if (next($headers) == true) {
                        $border .= '┼';
                    }
                }
                $border .= '┤';
                self::newLine();
                self::white($border);

                foreach ($rows as $row) {
                    $hasNextRow = next($rows) == true;
                    self::newLine();
                    self::white('│');
                    $border = $hasNextRow ? '├' : '└';
                    foreach ($row as $index => $column) {
                        self::info(' ' . $column . str_repeat(' ', (isset($widths[$index]) ? $widths[$index] : HelperString::width($column) + 1) - HelperString::width($column) - 1));
                        self::white('│');
                        $border .= str_repeat('─', isset($widths[$index]) ? $widths[$index] : HelperString::width($column) + 1);
                        if (next($row) == true) {
                            $border .= $hasNextRow ? '┼' : '┴';
                        }
                    }
                    $border .= $hasNextRow ? '┤' : '┘';
                    self::newLine();
                    self::white($border);
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
                    self::success($border);
                }
                self::newLine();

            } catch (\Exception $e) {
                self::error('Table columns or rows is not match with each other!');
            }
        } else {
            echo '<table><tr><th>' . implode('</th><th>', $headers) . '</th></tr>';
            foreach ($rows as $row) {
                echo '<tr><td>' . implode('</td><td>', $row) . '</td></tr>';
            }
            echo '</table>';
        }
    }

    protected static function startProgressBar($totalJob, $description = null)
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

    protected static function nextStepProgressBar($jobs = 1, $totalJobs = 0)
    {
        self::$ProgressBar['completed'] = self::$ProgressBar['completed'] + $jobs;
        self::$ProgressBar['totalJobs'] = self::$ProgressBar['totalJobs'] + $totalJobs;
        if (self::$ProgressBar['completed'] > self::$ProgressBar['totalJobs']) {
            self::$ProgressBar['totalJobs'] = self::$ProgressBar['completed'];
        }
        self::$ProgressBar['percent'] = floor(self::$ProgressBar['completed'] * 100 / self::$ProgressBar['totalJobs']);
        self::$ProgressBar['pixel'] = floor(self::$ProgressBar['percent'] / 4);
        self::$ProgressBar['emptyPixel'] = 25 - self::$ProgressBar['pixel'] > 0 ? 25 - self::$ProgressBar['pixel'] : 0;
        if (!self::$isHtml) {
            self::moveUp();
            self::clearLine();
            self::info(sprintf("{%s%s} %s (%d/%d) \t%s\n", str_repeat('▓', self::$ProgressBar['pixel']), str_repeat('░', self::$ProgressBar['emptyPixel']), self::$ProgressBar['percent'] . '%', self::$ProgressBar['completed'], self::$ProgressBar['totalJobs'], self::$ProgressBar['description']));
        }
    }

    protected static function finishProgressBar($description = "")
    {
        self::$ProgressBar['completed'] = self::$ProgressBar['totalJobs'];
        self::$ProgressBar['percent'] = 100;
        self::$ProgressBar['pixel'] = 25;
        self::$ProgressBar['emptyPixel'] = 0;
        if (!self::$isHtml) {
            self::moveUp();
            self::clearLine();
        }
        self::success(sprintf("{%s%s} %s (%d/%d) \t%s \t%s\n", str_repeat(self::$isHtml ? "■" : '▓', self::$ProgressBar['pixel']), str_repeat('░', self::$ProgressBar['emptyPixel']), self::$ProgressBar['percent'] . '%', self::$ProgressBar['completed'], self::$ProgressBar['totalJobs'], self::$ProgressBar['description'], $description));
        self::$ProgressBar = [];
    }

    protected static function moveUp($lines = 1)
    {
        echo self::$isHtml ? "" : (sprintf("\x1b[%dA", $lines));
    }

    protected static function moveDown($lines = 1)
    {
        echo self::$isHtml ? "" : (sprintf("\x1b[%dB", $lines));
    }

    protected static function moveRight($columns = 1)
    {
        echo self::$isHtml ? "" : (sprintf("\x1b[%dC", $columns));
    }

    protected static function moveLeft($columns = 1)
    {
        echo self::$isHtml ? "" : (sprintf("\x1b[%dD", $columns));
    }

    protected static function moveToColumn($column)
    {
        echo self::$isHtml ? "" : (sprintf("\x1b[%dG", $column));
    }

    protected static function moveToPosition($column, $row)
    {
        echo self::$isHtml ? "" : (sprintf("\x1b[%d;%dH", $row + 1, $column));
    }

    /**
     * Clears all the output from the current line.
     */
    protected static function clearLine()
    {
        echo self::$isHtml ? "" : ("\x1b[2K");
    }

    /**
     * Clears all the output from the current line after the current position.
     */
    protected static function clearLineAfter()
    {
        echo self::$isHtml ? "" : ("\x1b[K");
    }

    /**
     * Clears all the output from the cursors' current position to the end of the screen.
     */
    protected static function clearOutput()
    {
        echo self::$isHtml ? "" : ("\x1b[0J");
    }

    /**
     * Clears the entire screen.
     */
    protected static function clearScreen()
    {
        echo self::$isHtml ? "" : ("\x1b[2J");
    }

    protected static function getColumnWidth($commands)
    {
        $widths = [];
        foreach ($commands as $command) {
            $widths[] = HelperString::width($command);
        }
        return $widths ? max($widths) + 2 : 0;
    }

    private static function findCommand(): void
    {
        $arguments = self::$argument;
        if (isset($arguments[1]) and HelperString::has($arguments[1], '--')) {
            array_splice($arguments, 1, 0, ['help']);
        }
        if (in_array('--h', $arguments) or in_array('--help', $arguments)) {
            self::$argument[] = '--c=' . $arguments[1];
            array_splice($arguments, 1, 1, ['help']);
        }
        self::$CommandSignature = $arguments[1] ?? "help";
        $commandSignatures = [];
        $command = self::getListCommand(self::$CommandSignature, $commandSignatures);
        if (!$command) {
            self::error(sprintf('Command "%s" is not defined.', self::$CommandSignature), false);
            self::findSimilarCommand(self::$CommandSignature, $commandSignatures);
        } else {
            self::parseCommand($command);
            call_user_func_array([$command['class'], "handle"], []);
        }
    }

    protected static function findSimilarCommand($CommandSignature, $commandSignatures, $percentSimilarity = 40): void
    {
        $closetCommand = [];
        $i = 0;
        do {
            list($tempClosetCommand, $percent) = HelperString::closest_word($CommandSignature, $commandSignatures, true);
            if ($percent >= $percentSimilarity) {
                $closetCommand[] = $tempClosetCommand;
                $i++;
                if (($key = array_search($tempClosetCommand, $commandSignatures)) !== false) {
                    unset($commandSignatures[$key]);
                }
            }
        } while ($percent >= $percentSimilarity and $i <= 4);
        switch (count($closetCommand)) {
            case 0:
                exit;
            case 1:
                self::moveUp();
                self::moveUp();
                if (self::confirm(sprintf(" Did you mean `%s`?", $closetCommand[0]))) {
                    self::$argument[1] = $closetCommand[0];
                    self::warning(sprintf("> php %s", implode(" ", self::$argument)));
                    self::newLine();
                    self::findCommand();
                } else
                    exit;
                break;
            default:
                $choice = self::choice('Did you mean one of these?', $closetCommand, null, false, 1);
                if ($choice == null or !isset($closetCommand[$choice]))
                    exit;
                self::$argument[1] = $closetCommand[$choice];
                self::warning(sprintf("> php %s", implode(" ", self::$argument)));
                self::newLine();
                self::findCommand();
        }
    }

    protected static function getListCommand($needCommand = null, &$commandSignatures = null)
    {
        $commands = self::getListCommandFile();
        $result = [];
        foreach ($commands as $command) {
            require_once($command['file']);
            if (class_exists($command['class']) and method_exists($command['class'], 'handle')) {
                try {
                    $class = new $command['class']();
                    $reflection = new ReflectionClass($class);
                    $property = $reflection->getProperty('signature');
                    $property->setAccessible(true);
                    $signature = $property->getValue($class);
                    if ($reflection->hasProperty('description')) {
                        $property = $reflection->getProperty('description');
                        $property->setAccessible(true);
                        $description = $property->getValue($class);
                    }
                    if ($reflection->hasProperty('arguments')) {
                        $property = $reflection->getProperty('arguments');
                        $property->setAccessible(true);
                        $arguments = $property->getValue($class);
                    }
                    if ($reflection->hasProperty('options')) {
                        $property = $reflection->getProperty('options');
                        $property->setAccessible(true);
                        $Options = $property->getValue($class);
                    }
                    $result[$signature] = [
                        'signature' => $signature,
                        'description' => isset($description) ? $description : "",
                        'arguments' => isset($arguments) ? $arguments : [],
                        'Options' => isset($Options) ? $Options : [],
                        'class' => $class
                    ];
                    $commandSignatures[] = $signature;
                    if (!is_null($needCommand) and $signature == $needCommand)
                        break;
                } catch (\ReflectionException $e) {
                }
            }
        }
        return is_null($needCommand) ? $result : (isset($result[$needCommand]) ? $result[$needCommand] : false);
    }

    private static function parseCommand($command)
    {
        $arguments = self::$argument;
        unset($arguments[0], $arguments[1]);
        $CommandArguments = [];
        $CommandOptions = [];
        $TempCommandArguments = [];
        $TempCommandOptions = [];
        foreach ($arguments as $argument) {
            if (HelperString::firstHas($argument, '--')) {
                $argument = HelperString::firstDelete($argument, '--');
                if (HelperString::has($argument, '='))
                    list($key, $value) = explode('=', $argument, 2);
                else
                    list($key, $value) = [$argument, null];
                $TempCommandOptions[$key] = $value;
            } else {
                $TempCommandArguments[] = $argument;
            }
        }
        $errors = [];
        foreach ($command['arguments'] as $index => $argument) {
            if (!is_array($argument))
                $argument[0] = $argument;
            if (isset($argument[1]) and $argument[1] and !isset($TempCommandArguments[$index]))
                $errors[] = sprintf('"%s"', $argument[0]);
            else
                $CommandArguments[$argument[0]] = $TempCommandArguments[$index] ?? ($argument[3] ?? null);
        }
        if (count($errors) > 0) {
            self::error(sprintf("Not enough arguments (missing: %s).", implode(", ", $errors)));
        }
        foreach ($command['Options'] as $index => $Option) {
            if (!is_array($Option))
                $Option[0] = $Option;

            if (isset($Option[3]) && $Option[3] === true)
                $CommandOptions[$Option[0]] = $TempCommandOptions[$Option[0]] ?? ($Option[3] ?? null);
            if (isset($Option[1])) {
                $CommandOptions[$Option[0]] = $TempCommandOptions[$Option[1]] ?? ($Option[3] ?? null);
                $CommandOptions[$Option[1]] = $TempCommandOptions[$Option[1]] ?? ($Option[3] ?? null);
            }
        }
        call_user_func_array([$command['class'], "setCommandData"], [$CommandArguments, $CommandOptions, implode(' ', self::$argument)]);
    }

    public static function setCommandData($arguments, $options, $Command)
    {
        self::$CommandArguments = $arguments;
        self::$CommandOptions = $options;
        self::$CommandEnter = $Command;
    }

    private static function getListCommandFile()
    {
        $path = Dir::path('~apps/');
        $appFiles = Finder::create()
            ->files()
            ->depth(1)
            ->name('app.php')
            ->in($path);

        $result = [];

        foreach ($appFiles as $appFile) {
            if ($appFile instanceof SplFileInfo) {
                $path = $appFile->getPathInfo()->getPathname();
                $packageName = $appFile->getPathInfo()->getFilename();
                $isEnable = AppBuilder::init($packageName)->get('enable');
                if (!$isEnable)
                    continue;

                $commandPath = $path  . '/command/';
                if (!is_dir($commandPath))
                    continue;

                $commandFiles = Finder::create()
                    ->files()
                    ->name('*.php')
                    ->in($commandPath);

                foreach ($commandFiles as $commandFile) {
                    if ($commandFile instanceof SplFileInfo) {
                        $className = $commandFile->getRelativePathname();
                        $className = HelperString::lastDelete($className, '.php');
                        $result[] = [
                            'package_name' => $packageName,
                            'class' => 'App\\' . $packageName . '\command\\' . $className,
                            'file' => $commandFile->getRealPath()
                        ];
                    }
                }
            }
        }
        $commandPath = Dir::path('~pincore/command/');
        $files = Finder::create()->files()->in($commandPath);
        foreach ($files as $file) {
            $path = $file->getPath();
            $folders = str_replace($commandPath, '', $file->getPathInfo() .'/');
            $baseName = $file->getFilenameWithoutExtension();
            $fileName = $file->getFilename();
            $result[] = [
                'package_name' => '~',
                'class' => 'Pinoox\\Command\\' . $folders . $baseName,
                'file' => $path . '/' . $fileName
            ];
        }
        return $result;
    }

    // Returns colored string
    protected static function getColoredString($string, $foreground_color = null, $background_color = null)
    {
        $colored_string = "";

        if (self::$isHtml) {
            $colored_string .= "<span style=\"";

            if (!is_null($foreground_color))
                $colored_string .= "color:" . $foreground_color . ";";
            if (!is_null($background_color))
                $colored_string .= "background-color:" . $background_color . ";";
            $colored_string .= "\">" . $string . "</span>";

        } else {
            // Check if given foreground color found
            if (isset(self::$foreground_colors[$foreground_color])) {
                $colored_string .= "\033[" . self::$foreground_colors[$foreground_color] . "m";
            }
            // Check if given background color found
            if (isset(self::$background_colors[$background_color])) {
                $colored_string .= "\033[" . self::$background_colors[$background_color] . "m";
            }

            // Add string and end coloring
            $colored_string .= $string . "\033[0m";
        }
        return $colored_string;
    }

    protected function chooseApp($packageName = null)
    {
        $this->cli = config('~cli');
        if (empty($packageName) && isset($this->cli['package']) && $this->cli['package'] != '~') {
            $this->cli['namespace'] = 'App\\' . $this->cli['package'];

            $this->success('Using cli config ');
            $this->info('`' . $this->cli['package'] . '`');
            $this->newLine();
        } else if (empty($packageName) || !($packageName == 'pincore' || $packageName == '~')) {
            $this->cli = $this->chooseFromApps($packageName);
        } else {
            $this->cli['path'] = Dir::path('~pincore');
            $this->cli['package'] = 'pincore';
            $this->cli['namespace'] = 'pinoox';
        }
    }

    protected function chooseFromApps($packageName = null)
    {
        try {
            $package = !empty($packageName) ? $packageName : $this->argument('package');
            if (empty($package)){
                $this->error('enter the package name!');
            }
            $app = AppBuilder::init($package)->get();
            if (is_null($app)) $this->error(sprintf('Can not find app with name `%s`!', $package));

            $namespace = "App\\" . $package . "\\";
            $app_path = Dir::path('~apps/' . $package);

            return ['path' => $app_path, 'package' => $package, 'namespace' => $namespace];

        } catch (\Exception $exception) {
            $this->error($exception->getMessage());
        }
    }

}