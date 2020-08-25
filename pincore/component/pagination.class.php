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

/*
 *
 * help Class Pagination
 *
 * for example :
 * 100 post
 * show 10 post in each page
 * in page 4
 *
 * < ... 2 3 [4] 5 6 ...>
 *
 * ###############################
 * number all row = 100
 * number row in each page = 10
 * count prev number and next number each page = 2
 * ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
 * $page = new Pagination(100,10,2);
 * $page->setCurrentPage(4);
 * ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
 * $page-> getCurrentPage() // 4
 * $page-> getPage() // array(2,3,4,5,6)
 * $page->getLimit() // "30,10"
 * $page->getStartLimit() // 30
 * $page-> getLastPage() // 10
 * $page-> getInfoPage() // get all info Pagination
 *^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^^
 * show theme manual or auto
 * auto =>
 * echo $page->printList("page=",$pattern = "")
 *
 *------- help pattern ------>>>
 *
 *
 *   << < ... 3 4 5 6 7 ... > >>
 * $pattern = first=<<|back=<|next=>|last=>>|going=...
 *
 * ++++++++++++++++++++++++++++
 *
 * << < ... 3 4 [5] 6 7 ... > >>
 * add $pattern => li:class:test|li-active:class:test active
 * <<<---- help pattern --------
 *
 * pattern +++++++++++++++++++++++++++++++++++++++++++++++
 * next=value   => example: next=>         1 2 3 >
 * It's used for display next page
 *
 * back=value   => example: back=<       < 1 2 3
 * It's used for display back page
 *
 * first=value  => example: first=<<    << 1 2 3
 * It's used for display first page
 *
 * last=value   => example: last=>>        1 2 3 >>
 * It's used for display last page
 *
 * going=value  => example: going=...      1 2 3 ...
 * It's used for display despite having more pages
 *
 * a:class:test           => example: <a class="test"></a>
 * a:id:test              => example: <a id="test"></a>
 * li:class:test          => example: <li class="test"></li>
 * li:id:test             => example: <a id="test"></a>
 * ul:class:test          => example: <ul class="test"></ul>
 * a:class:test1 test2    => example: <a class="test1 test2"></a>
 * a-active:class:active  => example: <a class="active"></a>
 * li-active:class:active => example: <li class="active"></li>
 * ...
 *
 */

class Pagination
{

    # number row in each page
    private $numberRows;

    # count prev number and next number each page
    private $numberBetween = 2;

    # number all row
    private $numberAllRows;

    # count of rows
    private $rowsCount;

    # The current page
    private $currentPage = 1;


    /*
     * set number all Row and number Rows and between prev and next number page
     */
    public function __construct($numberAllRows, $numberRows, $numberBetween = 2)
    {
        $this->rowsCount = $numberAllRows;
        $this->numberAllRows = $numberAllRows;
        $this->numberRows = $numberRows;
        $this->numberBetween = $numberBetween;
    }

    /*
     * set current page
     */
    public function setCurrentPage($currentPage)
    {
        if ($currentPage == 'first')
            $currentPage = 1;
        elseif ($currentPage == 'last')
            $currentPage = self::getLastPage();
        else if ($currentPage == 'middle')
            $currentPage = round(self::getLastPage() / 2);


        if ($currentPage <= 0) $currentPage = 1;
        else if ($currentPage > $this->getLastPage()) $currentPage = $this->getLastPage();
        $this->currentPage = $currentPage;
    }


    public function getPagerView($page, $url, $getPattern = '')
    {
        $sample = '<li [add]><a [url]>[name]</a></li>';
        $pattern = 'url=' . $url;
        if (!empty($getPattern)) {
            $pattern .= '|' . $getPattern;
        } else {
            $pattern .= '|add-active:class:active|prev=<|next=>|last=← ' . Lang::get('~pagination.last') . '|first=' . Lang::get('~pagination.first') . ' →|going=...|nourl= ';
        }

        return $page->printHtml($sample, $pattern);
    }

    /*
     * get show pages
     */
    function getPages()
    {
        $pager = $this->getCurrentPage();
        $down_page = $pager - $this->numberBetween;
        //   if($down_page != 1) $page_arr[]="*";
        $top_page = $pager + $this->numberBetween;
        for ($i = $down_page; $i <= $pager; $i++) {
            if ($i > 0) {
                $page_arr[] = $i;
            }
        }
        for ($i = $pager + 1; $i <= $top_page; $i++) {
            if ($i <= $this->getLastPage()) {
                $page_arr[] = $i;
            }
        }
        //  if(end($page_arr) != $this->getLastPage()) $page_arr[]="*";
        return $page_arr;
    }

    /*
     * get start limit
     */
    public function getStartLimit()
    {
        return ($this->numberRows * $this->getCurrentPage()) - $this->numberRows;
    }

    /*
     * get limit page for db
     */
    public function getLimit()
    {
        return $this->getStartLimit() . "," . $this->numberRows;
    }

    /*
     * get array limit page
     */
    public function getArrayLimit()
    {
        return array($this->getStartLimit(), $this->numberRows);
    }

    /*
     * get last page
     */
    public function getLastPage()
    {
        if ($this->numberAllRows == 0) $this->numberAllRows = 1;
        return (int)(ceil($this->numberAllRows / $this->numberRows));
    }

    /*
     * get current page
     */
    public function getCurrentPage()
    {
        return (int)$this->currentPage;
    }

    /*
     * get all info Paginaion
     */
    public function getInfoPage()
    {
        $prevPage = array();
        $nextPage = array();
        $current = $this->getCurrentPage();
        $pages = $this->getPages();
        $last = $this->getLastPage();
        foreach ($pages as $page) {
            if ($page > $current) {
                $nextPage[] = $page;
            } else if ($page < $current) {
                $prevPage[] = $page;
            }
        }
        $info = [];
        $info['page']['count'] = $this->rowsCount;
        $info['page']['all'] = $pages;
        $info['page']['first'] = 1;
        $info['page']['prev'] = (!empty($prevPage)) ? end($prevPage) : null;
        $info['page']['prevAll'] = $prevPage;
        $info['page']['current'] = $current;
        $info['page']['next'] = (!empty($nextPage)) ? $nextPage[0] : null;
        $info['page']['nextAll'] = $nextPage;
        $info['page']['last'] = $last;
        $info['page']['keepPrev'] = ((!empty($prevPage) && $prevPage[0] == 1) || empty($prevPage)) ? true : false;
        $info['page']['keepNext'] = ((!empty($nextPage) && end($nextPage) == $last) || empty($nextPage)) ? true : false;
        $info['numAllowRows'] = $this->numberRows;
        $info['numAllRows'] = $this->numberAllRows;
        $info['numBetween'] = $this->numberBetween;
        $info['startLimit'] = $this->getStartLimit();
        $info['limit'] = $this->getLimit();
        $info['limitArray'] = $this->getArrayLimit();

        return $info;
    }

    // print pagination as a list
    public function printList($url = "", $pattern = "")
    {
        $pattern = $this->getPattern($pattern);
        $info = $this->getInfoPage();
        $back = (!empty($info['page']['prevAll'])) ? $url . end($info['page']['prevAll']) : "#";
        $next = (!empty($info['page']['nextAll'])) ? $url . $info['page']['nextAll'][0] : "#";
        $current = $info['page']['current'];
        $pages = $info['page']['all'];
        $first = ($current != $info['page']['first']) ? $url . $info['page']['first'] : "#";
        $last = ($current != $info['page']['last']) ? $url . $info['page']['last'] : "#";
        $is_first = (isset($pattern['first'])) ? true : false;
        $is_last = (isset($pattern['last'])) ? true : false;
        $is_back = (isset($pattern['back'])) ? true : false;
        $is_next = (isset($pattern['next'])) ? true : false;
        $is_pointer = (isset($pattern['going'])) ? true : false;
        $a = (isset($pattern['a'])) ? $pattern['a'] : "";
        $ul = (isset($pattern['ul'])) ? $pattern['ul'] : "";
        $li = (isset($pattern['li'])) ? $pattern['li'] : "";
        $a_active = (isset($pattern['a-active'])) ? $pattern['a-active'] : $aprintHtml;
        $li_active = (isset($pattern['li-active'])) ? $pattern['li-active'] : $li;

        $result = '<ul' . $ul . '>' . "\n";
        if ($is_first) $result .= '<li' . $li . '><a' . $a . ' href="' . $first . '">' . $pattern['first'] . '</a></li>' . "\n";
        if ($is_back) $result .= '<li' . $li . '><a' . $a . ' href="' . $back . '">' . $pattern['back'] . '</a></li>' . "\n";
        if ($is_pointer) if ($pages[0] != $info['page']['first']) $result .= '<li' . $li . '><a' . $a . ' >' . $pattern['going'] . '</a></li>' . "\n";
        foreach ($pages as $page) {
            if ($page == $current)
                $result .= '<li' . $li_active . '><a' . $a_active . ' href="#">' . $page . '</a></li>' . "\n";
            else
                $result .= '<li' . $li . '><a' . $a . ' href="' . $url . $page . '">' . $page . '</a></li>' . "\n";
        }
        if ($is_pointer) if (end($pages) != $info['page']['last']) $result .= '<li' . $li . '><a' . $a . ' >' . $pattern['going'] . '</a></li>' . "\n";
        if ($is_next) $result .= '<li' . $li . '><a' . $a . ' href="' . $next . '">' . $pattern['next'] . '</a></li>' . "\n";
        if ($is_last) $result .= '<li' . $li . '><a' . $a . ' href="' . $last . '">' . $pattern['last'] . '</a></li>' . "\n";
        $result .= '</ul>';

        return $result;
    }

    // get array pattern
    private function getPattern($pattern)
    {
        $arrType = explode("|", $pattern);
        $result = array();

        foreach ($arrType as $type) {
            if (!strstr($type,'https://') && !strstr($type,'http://') && strstr($type, ':')) {
                $arr = explode(":", $type);
                if (count($arr) >= 3) {
                    if (isset($result[$arr[0]]))
                        $result[$arr[0]] .= " " . $arr[1] . '=' . '"' . $arr[2] . '"';
                    else
                        $result[$arr[0]] = " " . $arr[1] . '=' . '"' . $arr[2] . '"';
                }
            } else if (strstr($type, '=')) {
               
                $get_type = substr($type, 0, strpos($type, '='));
                $get_name = str_replace($get_type . "=", '', $type);
                $result[$get_type] = $get_name;
            } else {
                $result[$type] = $type;
            }
        }
        return $result;
    }

    //print raw html
    public function printHtml($sample, $pattern)
    {
        $pattern = $this->getPattern($pattern);
        $info = $this->getInfoPage();
        $current = $info['page']['current'];
        $pages = $info['page']['all'];
        $active = (isset($pattern['active'])) ? $pattern['active'] : '';
        $nourl = (isset($pattern['nourl'])) ? $pattern['nourl'] : '#';
        $url = (isset($pattern['url'])) ? $pattern['url'] : '';
        $isset_auto = (isset($pattern['auto'])) ? true : false;

        // get going
        $going = '';
        if (isset($pattern['going'])) {
            $going = $this->getReplaceTag($sample, '[page]', '');
            $going = $this->getReplaceTag($going, '[page]', $pattern['going']) . "\n";
            $going = $this->getReplaceTag($going, '[url]', '');

            $nameGoing = (!empty($pattern['going']) ? $pattern['going'] : '...');
            if (!empty($going)) $going = $this->getReplaceTag($going, '[name]', $nameGoing);
            if (isset($pattern['add-going']))
                $going = $this->getReplaceTag($going, '[add]', $pattern['add-going']);
            else
                $going = $this->getReplaceTag($going, '[add]');
        }

        $oldSample = $sample;
        if (!empty($url)) $sample = $this->getReplaceTag($sample, '[url]', $url);

        $off_active = $this->getReplaceTag($sample, '[active]');
        $old_Off_active = $this->getReplaceTag($oldSample, '[active]');

        $on_active = $this->getReplaceTag($oldSample, '[active]', $active);
        $on_active = $this->getReplaceTag($on_active, '[url]', $nourl);

        // get prev
        $back = '';
        if (isset($pattern['prev'])) {
            if ((!empty($info['page']['prevAll'])))
                $back = $this->getReplaceTag($off_active, '[page]', end($info['page']['prevAll']) . "\n");
            else if (!$isset_auto)
                $back = $this->getReplaceTag($old_Off_active, '[url]', $nourl) . "\n";

            $namePrev = (!empty($pattern['prev']) ? $pattern['prev'] : 'prev');
            if (!empty($back)) $back = $this->getReplaceTag($back, '[name]', $namePrev);
            if (isset($pattern['add-prev']))
                $back = $this->getReplaceTag($back, '[add]', $pattern['add-prev']);
            else
                $back = $this->getReplaceTag($back, '[add]');
        }

        // get next
        $next = '';
        if (isset($pattern['next'])) {
            if ((!empty($info['page']['nextAll'])))
                $next = $this->getReplaceTag($off_active, '[page]', $info['page']['nextAll'][0]) . "\n";
            else if (!$isset_auto)
                $next = $this->getReplaceTag($old_Off_active, '[url]', $nourl) . "\n";

            $nameNext = (!empty($pattern['next']) ? $pattern['next'] : 'next');
            if (!empty($next)) $next = $this->getReplaceTag($next, '[name]', $nameNext);
            if (isset($pattern['add-next']))
                $next = $this->getReplaceTag($next, '[add]', $pattern['add-next']);
            else
                $next = $this->getReplaceTag($next, '[add]');


        }

        // get first
        $first = '';
        if (isset($pattern['first'])) {
            if ($current != $info['page']['first'])
                $first = $this->getReplaceTag($off_active, '[page]', $info['page']['first']) . "\n";
            else if (!$isset_auto)
                $first = $this->getReplaceTag($old_Off_active, '[url]', $nourl) . "\n";

            $nameFirst = (!empty($pattern['first']) ? $pattern['first'] : 'first');
            if (!empty($first)) $first = $this->getReplaceTag($first, '[name]', $nameFirst);
            if (isset($pattern['add-first']))
                $first = $this->getReplaceTag($first, '[add]', $pattern['add-first']);
            else
                $first = $this->getReplaceTag($first, '[add]');


        }

        // get last
        $last = '';
        if (isset($pattern['last'])) {
            if ($current != $info['page']['last'])
                $last = $this->getReplaceTag($off_active, '[page]', $info['page']['last']) . "\n";
            else if (!$isset_auto)
                $last = $this->getReplaceTag($old_Off_active, '[url]', $nourl) . "\n";

            $nameLast = (!empty($pattern['last']) ? $pattern['last'] : 'last');
            if (!empty($last)) $last = $this->getReplaceTag($last, '[name]', $nameLast);
            if (isset($pattern['add-last']))
                $last = $this->getReplaceTag($last, '[add]', $pattern['add-last']);
            else
                $last = $this->getReplaceTag($last, '[add]');

        }

        if (isset($pattern['add-active']))
            $on_active = $this->getReplaceTag($on_active, '[add]', $pattern['add-active']);
        else
            $on_active = $this->getReplaceTag($on_active, '[add]');
        if (isset($pattern['add']))
            $off_active = $this->getReplaceTag($off_active, '[add]', $pattern['add']);
        else
            $off_active = $this->getReplaceTag($off_active, '[add]');


        $on_active = $this->getReplaceTag($on_active, '[name]', '[page]');
        $off_active = $this->getReplaceTag($off_active, '[name]', '[page]');

        $result = '';
        $result .= $first;
        $result .= $back;
        if ($pages[0] != $info['page']['first']) $result .= $going;
        foreach ($pages as $page) {
            if ($page == $current) {
                $result .= $this->getReplaceTag($on_active, '[page]', $page) . "\n";
            } else {
                $result .= $this->getReplaceTag($off_active, '[page]', $page) . "\n";
            }
        }
        if (end($pages) != $info['page']['last']) $result .= $going;
        $result .= $next;
        $result .= $last;

        return $result;
    }

    private function getReplaceTag($string, $tag, $replace = '')
    {
        return str_replace($tag, $replace, $string);
    }
}