<?php
/**
 *      ****  *  *     *  ****  ****  *    *
 *      *  *  *  * *   *  *  *  *  *   *  *
 *      ****  *  *  *  *  *  *  *  *    *
 *      *     *  *   * *  *  *  *  *   *  *
 *      *     *  *    **  ****  ****  *    *
 * @license    https://opensource.org/licenses/MIT MIT License
 * @link       pinoox.com
 * @copyright  pinoox
 */


namespace App\com_pinoox_manager\Controller;

use Pinoox\Component\Http\Http;
use Pinoox\Portal\Config;
use Pinoox\Portal\Url;

class AccountController extends Api
{
    public function getPinooxAuth()
    {
        $token_key = config('connect.token_key');
        $response = Http::post('https://www.pinoox.com/api/manager/v1/account/getData', [
            'json' => [
                'remote_url' => Url::site(),
                'token_key' => $token_key,
            ],
        ]);

        $data = $response->toArray();
        if ($data['status']) {
            return $data['result'];
        }

        return null;
    }

    public function connect()
    {
        $response = Http::post('https://www.pinoox.com/api/manager/v1/account/getToken', [
            'json' => [
                'remote_url' => Url::site(),
            ],
        ]);

        $array = $response->toArray();
        if (!empty($array['token_key'])) {
            Config::name('connect')
                ->set('token_key', $array['token_key'])
                ->save();

            return $this->message($array['token_key']);
        }

        return $this->message(null, false);
    }

    public function getConnectData()
    {
        $connect =  Config::name('connect')->get();
        return !empty($connect)? $connect : '';
    }

    public function logout()
    {
        Config::name('connect')
            ->set('token_key', null)
            ->save();
    }
}