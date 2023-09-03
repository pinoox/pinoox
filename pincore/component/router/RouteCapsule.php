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


namespace pinoox\component\router;

use pinoox\component\helpers\HelperString;
use Symfony\Component\Routing\Route as RouteSymfony;

class RouteCapsule extends RouteSymfony
{
    public function setPath(string $pattern): static
    {
        $pattern = HelperString::lastDelete($pattern,'/');
        return parent::setPath($pattern);
    }
}
