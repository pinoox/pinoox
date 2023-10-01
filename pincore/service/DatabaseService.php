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

namespace pinoox\service;

use pinoox\component\interfaces\ServiceInterface;
use pinoox\portal\DB;

class DatabaseService implements ServiceInterface
{

    public function _run()
    {
        DB::getConnection();
    }

    public function _stop()
    {
    }
}

