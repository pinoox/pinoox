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
    
namespace pinoox\service;

use pinoox\portal\Config;
use pinoox\component\interfaces\ServiceInterface;
use pinoox\component\Session;
use pinoox\model\PincoreModel;

class SessionService implements ServiceInterface
{

    public function after()
    {
        $dbConfig = Config::get('~database');
        if (empty($dbConfig) || isset($dbConfig['isLock']) || !PincoreModel::$db->tableExists('session'))
            $store_in_file = true;
        else
            $store_in_file = false;
    }

    public function before()
    {
    }

    public function handle()
    {

    }

    public function _run()
    {
        // TODO: Implement _run() method.
    }

    public function _stop()
    {
        // TODO: Implement _stop() method.
    }
}