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

use App\com_pinoox_manager\Component\LangHelper;
use App\com_pinoox_manager\Component\AppHelper;
use App\com_pinoox_manager\Component\WidgetHelper;
use App\com_pinoox_manager\Component\WallpaperHelper;
use Pinoox\Component\Http\Request;
use Pinoox\Component\Http\Response;
use Pinoox\Component\Kernel\Controller\ApiController;
use Pinoox\Portal\App\App;
use Pinoox\Portal\Config;
use Pinoox\Portal\Lang;

class OptionController extends ApiController
{
    private function syncBackgroundOption(array $options): string
    {
        $saved = (string) ($options['background'] ?? '');
        $background = WallpaperHelper::resolve($saved);
        if ($background !== $saved) {
            Config::name('options')
                ->set('background', $background)
                ->save();
        }
        return $background;
    }

    public function toggleDockPin(string $packageName)
    {
        if (!AppHelper::getOne($packageName))
            return $this->deny('manager.app_not_found');
        $options = Config::name('options')->get() ?? [];
        $pins = $options['dock_pins'] ?? null;
        if (!is_array($pins))
            $pins = [];
        $wasPinned = in_array($packageName, $pins, true);
        if ($wasPinned) {
            $pins = array_values(array_filter($pins, fn($pkg) => $pkg !== $packageName));
        } else {
            $pins[] = $packageName;
        }
        Config::name('options')
            ->set('dock_pins', $pins)
            ->save();

        return $this->ok([
            'dock_pins' => $pins,
            'pinned' => !$wasPinned,
        ]);
    }

    private function normalizeAppViewMode(mixed $mode): string
    {
        $mode = is_string($mode) ? strtolower($mode) : '';

        return in_array($mode, ['simple', 'advanced'], true) ? $mode : 'simple';
    }

    public function getOptions()
    {
        $options = Config::name('options')->get() ?? [];
        $options['app_view_mode'] = $this->normalizeAppViewMode($options['app_view_mode'] ?? null);
        $options['lang'] = app()->lang();
        $options['wallpapers'] = WallpaperHelper::all();
        $options['defaultBackground'] = WallpaperHelper::defaultId();
        $options['background'] = $this->syncBackgroundOption($options);
        if (!array_key_exists('dock_pins', $options))
            $options['dock_pins'] = null;
        $options['widgets'] = WidgetHelper::all();
        return $options;
    }

    public function changeBackground($name)
    {
        $name = pathinfo(basename((string) $name), PATHINFO_FILENAME);
        if (WallpaperHelper::exists($name)) {
            Config::name('options')
                ->set('background', $name)
                ->save();

            return $this->message('manager.background_changed_successfully');
        }

        return $this->deny('manager.background_change_failed');
    }

    public function wallpaper(string $name): Response
    {
        $content = WallpaperHelper::contents($name);
        if ($content === null)
            return response('Not found', 404);
        return response(
            $content,
            200,
            [
                'Content-Type' => WallpaperHelper::mimeType($name),
                'Cache-Control' => 'public, max-age=86400',
            ]
        );
    }

    public function uploadWallpaper(Request $request)
    {
        if (!$request->files->has('wallpaper'))
            return $this->error('manager.invalid_request');
        $this->validated($request, [
            'wallpaper' => 'required|image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);
        $wallpaper = WallpaperHelper::upload($request->files->get('wallpaper'));
        if (!$wallpaper)
            return $this->error('manager.error_happened');

        return $this->message('manager.wallpaper_uploaded_successfully', [
            'wallpaper' => $wallpaper,
            'wallpapers' => WallpaperHelper::all(),
        ]);
    }

    public function deleteWallpaper(string $name)
    {
        $id = pathinfo(basename($name), PATHINFO_FILENAME);
        if (!WallpaperHelper::delete($name))
            return $this->error('manager.error_happened');
        $options = Config::name('options')->get() ?? [];
        if ((string) ($options['background'] ?? '') === $id) {
            Config::name('options')
                ->set('background', WallpaperHelper::defaultId())
                ->save();
        }
        return $this->message('manager.wallpaper_deleted_successfully', [
            'wallpapers' => WallpaperHelper::all(),
            'defaultBackground' => WallpaperHelper::defaultId(),
            'background' => $this->syncBackgroundOption(Config::name('options')->get() ?? []),
        ]);
    }

    public function changeLockTime($minutes = 0)
    {
        $minutes = (int) $minutes;
        $lock_time = (int) config('options.lock_time', 0);
        if ($minutes >= 0 && $lock_time !== $minutes) {
            Config::name('options')
                ->set('lock_time', $minutes)
                ->save();

            return $this->message('manager.lock_time_changed_successfully');
        }

        return $this->deny('manager.lock_time_change_failed');
    }

    public function changeLang($lang)
    {
        $lang = strtolower($lang);
        App::set('lang', $lang)->save();
        Lang::setLocale($lang);
        $total_lang = LangHelper::all();
        $direction = $total_lang['manager']['direction'];
        return ['lang' => $total_lang, 'direction' => $direction];
    }

    public function changeAppViewMode(string $mode)
    {
        $mode = $this->normalizeAppViewMode($mode);
        $current = $this->normalizeAppViewMode(Config::name('options')->get('app_view_mode'));

        if ($current === $mode) {
            return $this->deny('manager.app_view_mode_unchanged');
        }

        Config::name('options')
            ->set('app_view_mode', $mode)
            ->save();

        return $this->message('manager.saved_successfully', $mode);
    }
}

