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

use Illuminate\Database\Connection;
use \Illuminate\Database\Schema\Builder;
use Illuminate\Database\Capsule\Manager as Capsule;

class DatabaseManager
{
    private Capsule $capsule;

    public function __construct(array $config)
    {
        $this->capsule = new Capsule;

        $this->capsule->addConnection($config);

        //Make this Capsule instance available globally.
        $this->capsule->setAsGlobal();

        // Setup the Eloquent ORM... (optional; unless you've used setEventDispatcher())
        $this->capsule->bootEloquent();
    }

    public function setPrefix(string $prefix): void
    {
        $this->capsule->getConnection()->setTablePrefix($prefix);
    }

    public function getSchema(): Builder
    {
        return $this->capsule->schema();
    }

    public function getCapsule(): Capsule
    {
        return $this->capsule;
    }

    public function run(): Connection
    {
        return $this->capsule->getConnection();
    }


}