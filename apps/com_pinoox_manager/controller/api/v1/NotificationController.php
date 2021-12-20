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

use pinoox\app\com_pinoox_manager\component\Notification;
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
        $result = Notification::getAll();
        $result = array_map(function ($ntf) {
            $ntf['insert_jDate'] = Date::j('d F Y', $ntf['insert_date']);
            return $ntf;
        }, $result);

        return $result;
    }

    public function hide()
    {
        $ntf_id = Request::inputOne('ntf_id');

        $status = false;
        if ($ntf_id) $status = Notification::hide($ntf_id);

        Response::json('', $status);
    }

    public function seen()
    {
        $notifications = Request::inputOne('notifications');

        if (is_array($notifications)) {
            foreach ($notifications as $notification) {
                $ntf_id = is_array($notification) ? $notification['ntf_id'] : $notification;
                Notification::seen($ntf_id);
            }
        }
        Response::json('', true);
    }
}