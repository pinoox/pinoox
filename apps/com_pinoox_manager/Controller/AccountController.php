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
use Symfony\Contracts\HttpClient\ResponseInterface;

class AccountController extends Api
{
    private function decodeResponse(?ResponseInterface $response): array
    {
        if (!$response)
            return [];

        return json_decode($response->getContent(), true) ?? [];
    }

    public function getPinooxAuth()
    {
        $token_key = config('connect.token_key');
        $response = Http::post('https://www.pinoox.com/api/manager/v1/account/getData', [
            'json' => [
                'remote_url' => Url::origin(),
                'token_key' => $token_key,
            ],
        ]);

        $data = $this->decodeResponse($response);
        if (!empty($data['status']))
            return $data['result'];

        return null;
    }

    public function connect()
    {
        $response = Http::post('https://www.pinoox.com/api/manager/v1/account/getToken', [
            'json' => [
                'remote_url' => Url::origin(),
            ],
        ]);

        $array = $this->decodeResponse($response);
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
        $connect = Config::name('connect')->get();
        return !empty($connect) ? $connect : '';
    }

    public function logout()
    {
        Config::name('connect')
            ->set('token_key', null)
            ->save();

        return $this->message('logout');
    }
}
