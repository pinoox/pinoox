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
use Pinoox\Component\Http\Request;
use Pinoox\Component\Kernel\Controller\ApiController;
use Pinoox\Portal\Date;

class NotificationController extends ApiController
{
    public function index()
    {
        $result = NotificationHelper::getAll();
        $result = array_map(function ($ntf) {
            $ntf['insert_jDate'] = Date::jalali($ntf['insert_date'])->format('d F Y');
            return $ntf;
        }, $result);

        return $this->ok($result);
    }

    public function hide(Request $request)
    {
        $ntf_id = $request->payload('ntf_id');
        $status = $ntf_id && NotificationHelper::updateStatus($ntf_id, NotificationHelper::hide);

        return $status
            ? $this->message('manager.done_successfully')
            : $this->deny('manager.error_happened');
    }

    public function seen(Request $request)
    {
        $notifications = $request->payload('notifications', []);

        if (is_array($notifications)) {
            foreach ($notifications as $notification) {
                $ntf_id = is_array($notification) ? ($notification['ntf_id'] ?? null) : $notification;
                if ($ntf_id)
                    NotificationHelper::updateStatus($ntf_id, NotificationHelper::seen);
            }
        }

        return $this->ok();
    }
}

