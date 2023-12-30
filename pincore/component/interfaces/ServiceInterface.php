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
namespace Pinoox\Component\Interfaces;

interface ServiceInterface
{
    /**
     * Run service
     *
     * @return mixed
     */
    public function _run();

    /**
     * Stop service
     *
     * @return mixed
     */
    public function _stop();
}
    
