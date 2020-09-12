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


use pinoox\app\com_pinoox_manager\component\Wizard;
use pinoox\component\Response;

class TemplateController extends MasterConfiguration
{
    public function get()
    {

    }

    public function install($uid, $packageName)
    {
        if (empty($packageName))
            Response::json(rlang('manager.request_install_template_not_valid'), false);

        $file = Wizard::get_downloaded_template($uid);
        Wizard::installTemplate($file, $packageName);
        Response::json(rlang('manager.done_successfully'), true);
    }
}
