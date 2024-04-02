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
use App\com_pinoox_manager\Model\LangModel;
use Pinoox\Portal\App\App;
use Pinoox\Portal\Config;
use Pinoox\Portal\Lang;

class OptionController extends ApiController
{
    public function changeBackground($name)
    {
        $path = assets('dist/images/backgrounds/' . $name . '.jpg');
        if (is_file($path)) {
            Config::name('options')
                ->set('background', $name)
                ->save();
            return $this->message($name);
        }

        return $this->message($name, false);
    }

    public function changeLockTime(int $minutes = 0)
    {
        $lock_time = config('options.lock_time');
        if (is_int($minutes) && $minutes >= 0 && $lock_time != $minutes) {
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
        App::set('lang', $lang)
            ->save();
        Lang::setLocale($lang);
        $total_lang = LangHelper::all();;
        $direction = $total_lang['manager']['direction'];
        return ['lang' => $total_lang, 'direction' => $direction];
    }
}