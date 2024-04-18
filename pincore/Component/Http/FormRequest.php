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

use Illuminate\Support\MessageBag;
use Illuminate\Support\ValidatedInput;
use Pinoox\Component\Validation\AuthorizationException;
use Pinoox\Component\Validation\ValidationException;
use Illuminate\Validation\Validator;
use Symfony\Component\HttpFoundation\InputBag;

abstract class FormRequest
{
    const AUTHORIZATION = 'authorization';
    const VALIDATION = 'validation';
    protected $stopOnFirstFailure = false;

    public Request $global;
    protected Validator $validator;

    protected $check = true;
    protected $errorBag = 'default';

    public function __construct(Request $request)
    {
        $this->global = $request;
        $this->validator = $this->createValidator();
    }

    /**
     * @throws \Illuminate\Validation\ValidationException
     */
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

    /**
     * @throws \Illuminate\Validation\ValidationException
     */
    public function validated($key = null, $default = null)
    {
        return data_get($this->validation()->validated(), $key, $default);
    }

    protected function createValidator(): Validator
    {
        $validator = $this->global->getValidation()
            ->make(
                $this->data(),
                $this->rules(),
                $this->messages(),
                $this->attributes()
            )->stopOnFirstFailure($this->stopOnFirstFailure);

        // add after
        $this->addAfterArrayToValidator($validator);

        return $this->with($validator);
    }

    protected function addAfterArrayToValidator(Validator $validator): void
    {
        $validator->after($this->after());
    }

    public function validation(): Validator
    {
        return $this->validator;
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

    public function after(): array|callable
    {
        return [];
    }

    protected function prepend()
    {
        // ...
    }

    public function input(): InputBag
    {
        return $this->global->input();
    }

    public function has(string $key): bool
    {
        return $this->input()->has($key);
    }

    public function all(): array
    {
        return $this->input()->all();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if ($this->has($key)) {
            return $this->all()[$key];
        }

        return $default;
    }

    public function merge(array $input): static
    {
        $this->input()->add($input);

        return $this;
    }

    public function set(string $key, mixed $value): static
    {
        $this->input()->set($key, $value);

        return $this;
    }

    public function replace(array $input): static
    {
        $this->input()->replace($input);

        return $this;
    }

    public function filter(string $key, mixed $default = null, int $filter = \FILTER_DEFAULT, mixed $options = []): mixed
    {
        return $this->input()->filter($key, $default, $filter, $options);
    }

    public function __get($key)
    {
        return $this->input()->get($key);
    }

    public function __set(string $name, $value): void
    {
        $this->input()->set($name, $value);
    }

    public function __resolve()
    {
        $this->prepend();

        if (!$this->check)
            return;

        $this->check();
    }

    /**
     * @throws \Illuminate\Validation\ValidationException
     * @throws AuthorizationException
     */
    public function check()
    {
        if (!$this->passesAuthorization()) {
            $this->failedAuthorization();
        }

        $instance = $this->getValidatorInstance();

        if ($instance->fails()) {
            $this->failedValidation($instance);
        }

        return $this->validated();
    }

    /**
     * Handle the failure based on the type of error.
     *
     * @param string $type The type of error. Possible values are 'authorization' or 'validation'.
     * @return void
     */
    protected function failed(string $type)
    {
        // ...
    }

    public function with(Validator $validator): Validator
    {
        return $validator;
    }


    protected function getValidatorInstance(): Validator
    {
        return $this->validation();
    }

    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $this->failed(self::VALIDATION);
        throw (new ValidationException($validator))
            ->errorBag($this->errorBag);
    }

    public function passes(): bool
    {
        return $this->passesAuthorization() && $this->passesValidation();
    }

    public function fails(): bool
    {
        return !$this->passesAuthorization() || !$this->passesValidation();
    }

    protected function passesAuthorization(): bool
    {
        return $this->authorize();
    }

    protected function passesValidation(): bool
    {
        return $this->validation()->passes();
    }

    protected function failedAuthorization()
    {
        $this->failed(self::AUTHORIZATION);
        throw new AuthorizationException();
    }

    protected function errors(): MessageBag
    {
        return $this->validator->errors();
    }
}
