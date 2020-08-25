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
 * This component help you to generate tree and nested structure by give dataset and specific index
 *
 * @package pinoox\component
 *
 */
class Tree
{
    /**
     * @var bool
     */
    public $maxDepth = false;

    /**
     * @var
     */
    public $openParentHTMLTags;
    /**
     * @var
     */
    public $closeParentHTMLTags;
    /**
     * @var
     */
    public $openInnerHTMLTags;

    /**
     * @var
     */
    public $closeInnerHTMLTags;

    /**
     * @var
     */
    public $fields;

    /**
     * Create a nested tree
     *
     * @param array $records pass an array
     * @param string $parentName determine specific index of array for create nested tree based on it
     * @param string $field
     * @param string $childrenName determine name of nested tree name
     * @param int $fieldVal
     * @return array|void
     */
    public function createTree(&$records, $parentName, $field, $childrenName = 'children', $fieldVal = 0)
    {
        if ($records == null)
            return;
        $branch = array();
        foreach ($records as $r) {
            if ($r[$parentName] == $fieldVal) {
                $children = $this->createTree($records, $parentName, $field, $childrenName, $r[$field]);
                if ($children) {
                    $r[$childrenName] = $children;
                }
                $branch[] = $r;
            }
        }
        if (!empty($branch)) {
            /** @var array $branch */
            return $branch;
        }
        return null;
    }


    public function displayTree($tree_array, $children_field = 'children', $recursionDepth = 0)
    {
        if ($this->maxDepth && ($recursionDepth == $this->maxDepth)) return;


        echo $this->openParentHTMLTags;
        foreach ($tree_array as $row) {

            echo $this->parseValues($row, $this->openInnerHTMLTags, $recursionDepth);
            ///////start tag
            if (isset($row[$children_field])) {
                $this->displayTree($row[$children_field], $children_field, $recursionDepth + 1);
            }
            ///////end inner tag
            echo $this->closeInnerHTMLTags;
        }
        ///////end parent tag
        echo $this->closeParentHTMLTags;
    }


    private function parseValues($row, $html, $recursionDepth = 0)
    {

        if (empty($this->fields)) {
            return new Exception("fields cannot to be empty");
            exit;
        }

        $html = preg_replace("/\?\=level/", $recursionDepth, $html);

        foreach ($this->fields as $f) {
            if (validateDate($row[$f]))
                $row[$f] = convertDate($row[$f], true, "date");
            $html = preg_replace("/\?\=$f/", $row[$f], $html);
        }
        return $html;
    }


    /**
     *
     * @param $tree_array
     * @param string $children_fields
     * @param int $recursionDepth
     * @return false|string
     */
    public function getTreeHtml($tree_array, $children_fields = 'children', $recursionDepth = 0)
    {
        ob_start();
        $this->displayTree($tree_array, $children_fields, $recursionDepth);
        return ob_get_clean();
    }


}