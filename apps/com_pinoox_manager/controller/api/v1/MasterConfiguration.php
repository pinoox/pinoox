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

namespace pinoox\app\com_pinoox_manager\controller\api\v1;

use pinoox\app\com_pinoox_manager\controller\api\ApiConfiguration;
use pinoox\app\com_pinoox_manager\model\LangModel;
use pinoox\component\app\AppProvider;
use pinoox\component\Router;

class MasterConfiguration extends ApiConfiguration
{

    const manualPath = 'downloads/packages/manual/';

    public function __construct()
    {
        parent::__construct();
    }

}
    
