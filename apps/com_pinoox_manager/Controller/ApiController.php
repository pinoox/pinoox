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


use Pinoox\Component\Http\JsonResponse;
use Pinoox\Component\Http\ResponseException;
use Pinoox\Component\Kernel\Controller\Controller;

class ApiController extends Controller
{

    public static function message($message, $result = null, ?int $code = 200, bool $exception = false): JsonResponse
    {
        $message = is_string($message) ? t($message) : $message;
        $data = ["message" => $message];
        if (!is_null($result))
            $data['result'] = $result;
        $response = response()->json($data, $code);

        if ($exception)
            ResponseException::call($response);

        return $response;
    }

    public static function error($error, ?int $code = 422, bool $exception = false): JsonResponse
    {
        $error = is_string($error) ? t($error) : $error;
        $response = response()->json(["error" => $error], $code);
        if ($exception)
            ResponseException::call($response);

        return $response;
    }
}