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
namespace pinoox\app\com_pinoox_installer\controller\api\v1;

use pinoox\app\com_pinoox_installer\controller\api\ApiConfiguration;
use pinoox\component\app\AppProvider;

class MasterConfiguration extends ApiConfiguration
{

    public function __construct()
    {
        parent::__construct();
    }

    protected function getLang($lang = null)
    {
        $lang = empty($lang) ? AppProvider::get('lang') : $lang;
        return [
            'direction' => in_array($lang, ['fa', 'ar']) ? 'rtl' : 'ltr',
            'lang' => [
                'install' => rlang('install'),
                'user' => rlang('user'),
                'language' => rlang('language'),
            ]
        ];
    }
}
    
