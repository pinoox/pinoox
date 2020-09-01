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

use pinoox\app\com_pinoox_manager\model\NotificationModel;
use pinoox\component\Date;
use pinoox\component\Request;
use pinoox\component\Response;

class NotificationController extends LoginConfiguration
{

    public function _main()
    {
        Response::json($this->getNotifications(), true);
    }

    private function getNotifications()
    {
        $result = NotificationModel::fetch_all(null, false);
        array_map(function ($ntf) {
            $ntf['insert_jDate'] = Date::j('d F Y', $ntf['insert_date']);
            return $ntf;
        }, $result);

        return $result;
    }

    public function hide()
    {
        $ntf_id = Request::inputOne('ntf_id');

        $status = false;
        if ($ntf_id) $status = NotificationModel::update_seen($ntf_id, true);

        Response::json('', $status);
    }
}