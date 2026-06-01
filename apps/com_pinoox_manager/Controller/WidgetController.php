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


use App\com_pinoox_manager\Component\StorageHelper;
use App\com_pinoox_manager\Component\WidgetHelper;
use Carbon\Carbon;
use Morilog\Jalali\Jalalian;
use Pinoox\Component\Http\Request;

class WidgetController extends Api
{
    public function clock()
    {
        if ((app('lang') === 'fa')) {
            $now = Jalalian::now(new \DateTimeZone('Asia/Tehran'));
        } else {
            $now = Carbon::now();
        }

        return [
            'time' => time(),
            'date' => t('widget/clock.today') . ' ' . $now->format('d F Y'),
            'moment' => $now->format('H:i'),
        ];
    }

    public function storage()
    {
        $stats = StorageHelper::stats();
        $settings = StorageHelper::settings();

        return array_merge($stats, [
            'limit_gb' => $settings['limit_gb'],
            'default_path' => StorageHelper::defaultPath(),
            'mode' => $settings['mode'],
        ]);
    }

    public function browseStorage(Request $request)
    {
        $path = $request->query->get('path');

        return StorageHelper::browse(is_string($path) ? $path : null);
    }

    public function saveStorageSettings(Request $request)
    {
        $mode = (string) $request->getPayload()->get('mode', 'auto');
        $path = (string) $request->getPayload()->get('path', '');
        $limitGb = (float) $request->getPayload()->get('limit_gb', 0);

        $result = StorageHelper::saveSettings($mode, $path, $limitGb);

        if (empty($result['saved']))
            return self::error($result['message'] ?? 'ذخیره تنظیمات انجام نشد');

        return $result;
    }

    public function settings()
    {
        return [
            'widgets' => WidgetHelper::all(),
            'storage' => array_merge(
                StorageHelper::settings(),
                ['default_path' => StorageHelper::defaultPath()]
            ),
        ];
    }

    public function saveWidgets(Request $request)
    {
        $widgets = $request->getPayload()->get('widgets', []);

        if (!is_array($widgets))
            return self::error('فرمت داده نامعتبر است');

        $result = WidgetHelper::save($widgets);

        if (empty($result['saved']))
            return self::error($result['message'] ?? 'ذخیره تنظیمات انجام نشد');

        return $result;
    }
}
