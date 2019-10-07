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

class Tree
{
    /**
     * @var bool
     */
    public $maxDepth = false;
    public $openParentHTMLTags;
    public $closeParentHTMLTags;
    public $openInnerHTMLTags;
    public $closeInnerHTMLTags;
    public $fields;

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
        return $branch;
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


    public function getTreeHtml($tree_array, $children_fields = 'children', $recursionDepth = 0)
    {
        ob_start();
        $this->displayTree($tree_array, $children_fields, $recursionDepth);
        return ob_get_clean();
    }


}