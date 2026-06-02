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

namespace App\com_pinoox_manager\Controller;

use App\com_pinoox_manager\Component\NotificationHelper;
use Pinoox\Component\Date;
use Pinoox\Component\Http\Request;

class NotificationController extends Api
{
    public function index()
    {
        $result = NotificationHelper::getAll();
        $result = array_map(function ($ntf) {
            $ntf['insert_jDate'] = Date::j('d F Y', $ntf['insert_date']);
            return $ntf;
        }, $result);

        return $this->message(null, $result);
    }

    public function hide(Request $request)
    {
        $ntf_id = $request->json->get('ntf_id');
        $status = $ntf_id && NotificationHelper::updateStatus($ntf_id, NotificationHelper::hide);

        return $status ? $this->message(null) : $this->message(null, false);
    }

    public function seen(Request $request)
    {
        $notifications = $request->json->get('notifications', []);

        if (is_array($notifications)) {
            foreach ($notifications as $notification) {
                $ntf_id = is_array($notification) ? ($notification['ntf_id'] ?? null) : $notification;
                if ($ntf_id)
                    NotificationHelper::updateStatus($ntf_id, NotificationHelper::seen);
            }
        }

        return $this->message(null);
    }
}
