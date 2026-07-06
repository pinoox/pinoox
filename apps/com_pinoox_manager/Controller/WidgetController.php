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
use Pinoox\Component\Http\Request;
use Pinoox\Component\Kernel\Controller\ApiController;
use Pinoox\Portal\Date;

class WidgetController extends ApiController
{
    public function clock()
    {
        $timezone = Date::timezone();
        $manager = Date::usingCalendar(Date::calendar());
        $instant = Date::now($timezone);
        $timestamp = $instant->getTimestamp();

        return [
            'time' => $timestamp,
            'timestamp' => $timestamp,
            'timezone' => $timezone,
            'date' => $manager->display('now', 'full'),
            'moment' => $manager->display('now', 'time'),
        ];
    }

    public function storage()
    {
        $stats = StorageHelper::stats(cacheOnly: true);

        if (!empty($stats['size_pending'])) {
            @set_time_limit(30);
            $stats = StorageHelper::stats();
        }

        $settings = StorageHelper::settings();

        return array_merge($stats, [
            'limit_gb' => $settings['limit_gb'],
            'default_path' => StorageHelper::defaultPath(),
            'mode' => $settings['mode'],
        ]);
    }

    public function browseStorage(Request $request)
    {
        $path = $request->queryOne('path');

        return StorageHelper::browse(is_string($path) ? $path : null);
    }

    public function saveStorageSettings(Request $request)
    {
        $mode = (string) $request->payload('mode', 'auto');
        $path = (string) $request->payload('path', '');
        $limitGb = (float) $request->payload('limit_gb', 0);

        if (in_array($mode, ['directory', 'database', 'manual'], true))
            @set_time_limit(120);

        $result = StorageHelper::saveSettings($mode, $path, $limitGb);

        if (empty($result['saved']))
            return $this->error($result['message'] ?? 'manager.storage_settings_save_failed');

        return $this->message('manager.storage_settings_saved_successfully', $result);
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
        $widgets = $request->payload('widgets', []);

        if (!is_array($widgets))
            return $this->error('manager.invalid_payload');

        $result = WidgetHelper::save($widgets);

        if (empty($result['saved']))
            return $this->error($result['message'] ?? 'manager.storage_settings_save_failed');

        return $this->message('manager.widgets_saved_successfully', $result);
    }
}
