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


namespace Pinoox\Component\Http;

use Pinoox\Component\Helpers\Str;
use Pinoox\Portal\Url;
use Symfony\Component\HttpFoundation\RedirectResponse as RedirectResponseSymfony;

class RedirectResponse extends RedirectResponseSymfony
{
    public function setTargetUrl(string $url): static
    {
        if (!filter_var($url, FILTER_VALIDATE_URL) && !preg_match('/^https?:\/\/[^\s]+$/i', $url)) {
            $base = Url::get('^');
            if (Str::firstHas($url, $base))
                $url = Str::firstDelete($url, $base);
            $url = Url::get($url);
        }
        return parent::setTargetUrl($url);
    }
}