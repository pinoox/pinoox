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

namespace pinoox\component\interfaces;

interface ControllerInterface
{
    /**
     * Main method
     *
     * if not a parameter
     *
     * @return mixed
     */
    public function _main();

    /**
     * Exception method
     *
     * if it is a parameter and not a method for it
     *
     * @return mixed
     */
    public function _exception();
}