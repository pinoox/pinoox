<?php

/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @license    https://opensource.org/licenses/MIT MIT License
 * @link       pinoox.com
 * @copyright  pinoox
 */

namespace pinoox\component\database;

use Illuminate\Database\Capsule\Manager as Capsule;

class DatabaseManager extends Capsule
{
    public function __construct(array $config)
    {
        parent::__construct();

        // add default connection
        $this->addConnection($config);
        //Make this Capsule instance available globally.
        $this->setAsGlobal();
        // Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
        $this->bootEloquent();
    }

    public function setPrefix(string $prefix): void
    {
        $this->getConnection()->setTablePrefix($prefix);
    }
}