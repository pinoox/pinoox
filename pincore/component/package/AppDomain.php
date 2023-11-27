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
namespace pinoox\component\package;

use pinoox\component\Helpers\HelperString;
use pinoox\component\Url;
use pinoox\portal\Config;

class AppDomain
{
    private static function checkPatternDomain($arrSub, $arrMain)
    {
        $countSub = count($arrSub);
        $countMain = count($arrMain);

        if ($countMain < $countSub) return false;

        $arrSub = array_reverse($arrSub);
        $arrMain = array_reverse($arrMain);

        for ($i = 0; $i < $countSub; $i++) {
            if ($arrSub[$i] == '*') continue;
            if ($i == ($countSub - 1) && $countMain > $countSub) return false;
            $arr = explode(',', $arrSub[$i]);
            if (in_array($arrMain[$i], $arr)) continue;

            return false;
        }

        return true;
    }

    private static function checkDomain($url = null)
    {
        $new_url = empty($url) ? self::$url : $url;;
        $app_domain = Config::name('~domain')->get(Url::domain());
        if (empty($app_domain)) {
            $info = self::getByPatternDomain();
            if (!empty($info)) {
                $app_domain = $info['app'];
                $sub = $info['sub'];

                $multiSub = array_reverse(explode('.', $sub));
                $multiSub = implode('/', $multiSub);

                $app_domain = str_replace('{sub}', $sub, $app_domain);
                $app_domain = str_replace('{multi_sub}', $multiSub, $app_domain);
            }
        }

        if (!empty($app_domain)) {
            $app_domain = trim($app_domain, '/');
            self::$isDomain = true;
            $new_url = $app_domain . "/" . $new_url;
            if (empty($url)) {
                self::$url = $new_url;
            }
        }
        return $new_url;
    }

    private static function getByPatternDomain()
    {
        $domain = Config::name('~domain')->get(Url::domain());
        if (is_null($domain)) {
            $domains = Config::name('~domain')->get();

            foreach ($domains as $pattern => $area) {
                if (HelperString::has($pattern, '*')) {

                    list($sub, $domain) = HelperString::divTwoPart($pattern, '*', true, 1);
                    if (HelperString::lastHas(Url::domain(), $domain)) {
                        $currentDomain = HelperString::lastDelete(Url::domain(), $domain);

                        if ($sub == '*') {
                            return ['sub' => $currentDomain, 'app' => $area];
                        }

                        $arrMain = explode('.', $currentDomain);
                        $arrSub = explode('.', $sub);

                        if (self::checkPatternDomain($arrSub, $arrMain)) {
                            return ['sub' => $currentDomain, 'app' => $area];
                        }
                    }
                }
            }
        }
    }
}