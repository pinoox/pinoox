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

namespace App\com_pinoox_installer\Request;

use Pinoox\Component\Http\ApiFormRequest;

class SetupRequest extends ApiFormRequest
{
    public function rules(): array
    {
        return [
            'user.fname' => 'required|min:3',
            'user.lname' => 'required|min:3',
            'user.email' => 'required|email',
            'user.username' => 'required|alpha_dash:ascii|min:3',
            'user.password' => 'required|min:6',
            'db.host' => 'required',
            'db.database' => 'required',
            'db.username' => 'required',
            'db.password' => 'nullable|string',
            'db.prefix' => 'nullable|string',
            'db.port' => 'nullable|string',
        ];
    }
}

