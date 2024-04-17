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


namespace Pinoox\Component\Http;

use Illuminate\Support\ValidatedInput;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator;
use Pinoox\Component\Kernel\Exception;

abstract class FormRequest
{
    public Request $global;
    public $isAuto = false;
    protected $errorBag = 'default';

    public function __construct(Request $request)
    {
        $this->global = $request;
    }

    public function validate()
    {
        return $this->validation()->validate();
    }

    public function safe(array $keys = null): ValidatedInput|array
    {
        return $this->validation()->safe($keys);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function validated($key = null, $default = null): mixed
    {
        return data_get($this->validation()->validated(), $key, $default);
    }

    public function validation(): Validator
    {
        $validator = $this->global->getValidation()->make($this->data(), $this->rules(), $this->messages(), $this->attributes());
        return $this->withValidator($validator);
    }

    public function data(): array
    {
        return $this->global->all();
    }

    public function rules(): array
    {
        return [];
    }

    public function messages(): array
    {
        return [];
    }

    public function attributes(): array
    {
        return [];
    }

    public function prepend()
    {
        // ...
    }


    public function check()
    {
        if (!$this->passesAuthorization()) {
            $this->failedAuthorization();
        }

        $instance = $this->getValidatorInstance();

        if ($instance->fails()) {
            $this->failedValidation($instance);
        }
    }

    public function withValidator(Validator $validator): Validator
    {
        return $validator;
    }

    protected function getValidatorInstance(): Validator
    {
        return $this->validation();
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        throw (new ValidationException($validator))
            ->errorBag($this->errorBag);
    }

    protected function passesAuthorization()
    {
        if (method_exists($this, 'authorize')) {
            return $this->authorize();
        }

        return true;
    }

    protected function failedAuthorization()
    {
        throw new Exception('This action is unauthorized.',0);
    }
}
