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

use Pinoox\Component\Http\Response;

use Pinoox\Portal\App\App;

use Pinoox\Portal\Config;

use Pinoox\Portal\Lang;



class OptionController extends Api

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



    private function defaultDockPins(): array

    {

        $pins = [];

        foreach (AppHelper::getAll() as $app) {

            if (!empty($app['dock']))

                $pins[] = $app['package_name'];

        }

        return $pins;

    }



    public function toggleDockPin(string $packageName)

    {

        if (!AppHelper::getOne($packageName))

            return $this->message(null, false);



        $options = Config::name('options')->get() ?? [];

        $pins = $options['dock_pins'] ?? null;



        if (!is_array($pins))

            $pins = $this->defaultDockPins();



        $wasPinned = in_array($packageName, $pins, true);



        if ($wasPinned) {

            $pins = array_values(array_filter($pins, fn($pkg) => $pkg !== $packageName));

        } else {

            $pins[] = $packageName;

        }



        Config::name('options')

            ->set('dock_pins', $pins)

            ->save();



        return [

            'dock_pins' => $pins,

            'pinned' => !$wasPinned,

        ];

    }



    public function getOptions()

    {

        $options = Config::name('options')->get() ?? [];

        $options['lang'] = app('lang');

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



            return $this->message($name);

        }



        return $this->message($name, false);

    }



    public function wallpaper(string $name): Response

    {

        $file = WallpaperHelper::filePath($name);



        if (!$file)

            return response('Not found', 404);



        return response(

            file_get_contents($file),

            200,

            [

                'Content-Type' => WallpaperHelper::mimeType($file),

                'Cache-Control' => 'public, max-age=86400',

            ]

        );

    }



    public function changeLockTime($minutes = 0)

    {

        $minutes = (int) $minutes;

        $lock_time = (int) config('options.lock_time', 0);



        if ($minutes >= 0 && $lock_time !== $minutes) {

            Config::name('options')

                ->set('lock_time', $minutes)

                ->save();

            return $this->message($minutes);

        }



        return $this->message($minutes, false);

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

}


