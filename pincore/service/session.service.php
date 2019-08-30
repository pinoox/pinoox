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

use pinoox\component\Config;
use pinoox\component\interfaces\ServiceInterface;
use pinoox\component\Session;
use pinoox\model\PinooxDatabase;

class SessionService implements ServiceInterface
{

    public function _run()
    {
        $dbConfig = Config::get('~database');
        if (empty($dbConfig) || isset($dbConfig['isLock']) || !PinooxDatabase::$db->tableExists('session'))
            $store_in_file = true;
        else
            $store_in_file = false;

        $session = new Session($store_in_file);
        $session::gcProbability(0);
        $session->lifeTime(365, 'day');
        //$session->refresh_lifetime_in_requests(true);
        $session->securityToken(true);
        $session->encryption(false);
        $session->nonBlocking(false);
        $session->start();
    }

    public function _stop()
    {
    }
}
