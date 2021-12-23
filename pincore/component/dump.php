<?php

/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @author   Pinoox
 * @link https://www.pinoox.com/
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace pinoox\component;

/**
 * Dump Data
 * 
 *  you can use it like: Dump:r($data, $lable)
 * 
 */
class Dump
{
    /**
     *  data to be shown next output
     */
    public $data = array();

    /**
     * pre default styling
     */
    public $style = 'font-family: Menlo, monospace; color: #00ff48;padding:8px;;background:#191919; font-size: 11px !important; line-height: 17px !important; text-align: left;';

    /**
     * default config for output
     */
    public static $config = array(
        'width' => 'auto',
        'height' => 'auto',
    );

    // singleton instance
    public static $instance;

    /**
     * Add data to be shown next time Pre is rendered
     * 
     * @param $data 
     * @param $lable
     */
    public static function data($data = NULL, $label = NULL)
    {

        // get Pre instance
        $pre = self::instance();

        // $data will be cleared after it is output
        if (func_num_args())
            $pre->data[] = array('data' => $data, 'label' => $label);

        // return instance for method chaining or __toString
        return $pre;
    }

    /**
     * Shortcut -- Dump::add() -- same as Dump::data()
     * 
     * @param $data 
     * @param $lable
     */
    public static function add($data = NULL, $label = NULL)
    {
        return (func_num_args()) ? self::data($data, $label) : self::instance();
    }

    /**
     * Shortcut to Dump::render()
     * 
     * @param $data 
     * @param $lable
     */
    public static function r($data = NULL, $label = NULL)
    {
        return (func_num_args()) ? self::data($data, $label) : self::instance();
    }

    /**
     * Render all Dump data to a string and return
     * 
     * @param $data 
     * @param $lable
     */
    public static function render($data = NULL, $label = NULL)
    {

        // add data if passed
        $pre = (func_num_args()) ? self::data($data, $label) : self::instance();

        // return rendered string
        return (string) $pre;
    }

    /**
     * Simple wrapper for var_dump that outputs within a styled <pre> tag and fixes whitespace and formatting
     */
    public function __toString()
    {

        // extract config to this context for convenience
        extract(self::$config);

        // you can specify dimensions for a scrollble div
        if ($height !== 'auto' or $width !== 'auto') {

            // add "px" to height and width
            if (is_numeric($height) and !strstr($height, '%')) $height .= 'px';
            if (is_numeric($width) and !strstr($width, '%')) $width .= 'px';

            // all scrollables get a border
            $this->style .= " border: 1px solid #fff; padding: 10px; overflow-y: scroll; height: $height; width: $width;";
        }

        // styled pre tag
        $pre = '<pre style="' . $this->style . '">';
        $backtrace = debug_backtrace()[1];

        $pre .= "<div style='color: #ffd9ba'>" . $backtrace['file'] . " line: " . $backtrace['line']  . "</div>";
        // iterate over data objects and var_dump 'em
        foreach ($this->data as $data) {

            // pull out data and label
            extract($data);

            // capture var_dump
            ob_start();
            var_dump($data);
            $data = ob_get_clean();

            // special case for NULL values
            if (trim($data) == 'NULL')
                $data = '<span style="color: #ff4d00;">NULL</span>' . "\n";

            // add label
            if (!empty($label))
                $data = '<span style="color: #ff4d00; font-weight: bold; background-color: #eee; font-size: 11px; padding: 3px 5px;">' . $label . ":</span> $data";

            // compile list of class names by searching: object(PreObject)#4 (2) {
            preg_match_all('/object\(([A-Za-z0-9_]+)\)\#[0-9]+\ \([0-9]+\)\ {/', $data, $objects);

            // we have some objects w/ class names
            if (!empty($objects)) {

                // just get the list of object names, without duplicates
                $objects = array_unique($objects[1]);

                // fix all class names to look like this: stdClass#3 object(4) {
                $data = preg_replace('/object\(([A-Za-z0-9_]+)\)\#([0-9]+)\ \(([0-9]+)\)\ {/', '<span style="color: #ff4d00; font-weight: bold;">\\1</span> <span style="color: #0044ff;">#\\2</span> <span style="color: #ff7300;">object(\\3)</span> {', $data);

                // remove class name from private members
                foreach ($objects as $object)
                    $data = str_replace(':"' . $object . '"', '', $data);
            }

            // consistent styling of =>'s
            $arrow = '<span style="font-weight: bold; color: #aaa;"> => </span>';

            // style special case  NULL
            $data = preg_replace('/=>\s*NULL/i', '=> <span style="color: #ff7300;">NULL</span>', $data);

            // array keys are bolder
            $data = preg_replace('/\[\"([a-z0-9_\ \@+\-\(\):]+)\"(:?[a-z]*)\]\s*=>\s*/i', '<span style="color: #0044ff; font-weight: bold;">[\\1]</span>\\2' . $arrow, $data);

            // numeric keys are bolder
            $data = preg_replace('/\[([0-9]+)\]\s*=>\s*/', '<span style="color: #0044ff; font-weight: bold;">[\\1]</span>' . $arrow, $data);

            // style :private and :protected
            $data = preg_replace('/(:(private|protected))/', '<span style="color: #0044ff; font-style: italic;">\\1</span>', $data);

            // de-emphasize string labels
            $data = preg_replace('/string\(([0-9]+)\)/', '<span style="color: #ff7300;">string(\\1)</span>', $data);

            // de-emphasize int labels
            $data = preg_replace('/int\(([0-9-]+)\)/', '<span style="color: #ff7300;">int(<span style="color: #ff4d00;">\\1</span>)</span>', $data);

            // de-emphasize float labels
            $data = preg_replace('/float\(([0-9\.-]+)\)/', '<span style="color: #ff7300;">float(<span style="color: #ff4d00;">\\1</span>)</span>', $data);

            // de-emphasize bool label
            $data = preg_replace('/bool\(([A-Za-z]+)\)/', '<span style="color: #ff7300;">bool(<span style="text-transform: uppercase; color: #ff4d00;">\\1</span>)</span>', $data);
            // de-emphasize array labels
            $data = preg_replace('/array\(([0-9]+)\)/', '<span style="color: #ff7300;">array(\\1)</span>', $data);

            // boost spacing
            $data = preg_replace_callback('/^(\s*)(\S.*)$/m', function ($matches) {
                // number of spaces that var_dump gave it
                $indent = strlen($matches[1]);
                // each 2 spaces is one column
                $indent = ($indent > 0) ? str_repeat("    ", $indent / 2) : "";
                return $indent . $matches[2];
            }, $data);

            // style opening brackets
            $data = preg_replace('/^(.*)(<\/span>\ \{)$/m', '\\1<span style="color: #ff7300;">\\2</span>', $data);

            // style closing brackets
            $data = preg_replace('/^(\s*)(\})$/m', '\\1<span style="color: #ff7300;">\\2</span>', $data);

            // add to pre output
            $pre .= "$data";
        }

        // close pre tag
        $pre .= "</pre>\n\n";

        // reset data
        $this->data = array();

        // return output
        return $pre;
    }

    /**
     * uses singleton pattern to take advantage of __toString magic
     */
    public static function instance()
    {

        // create a new instance
        if (!isset(self::$instance))
            self::$instance = new Dump;

        // return instance
        return self::$instance;
    }
}
