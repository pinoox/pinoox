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


namespace Pinoox\Component\Validation;

use Illuminate\Support\MessageBag;
use Illuminate\Validation\ValidationException as ValidationExceptionIlluminate;

class ValidationException extends ValidationExceptionIlluminate
{
    public function errs(): MessageBag
    {
        return $this->validator->errors();
    }

    public function first($key = null, $format = null): string
    {
        return $this->validator->errors()->first($key, $format);
    }
}