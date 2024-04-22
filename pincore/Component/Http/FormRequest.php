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
use Pinoox\Component\Upload\FileUploader;
use Pinoox\Component\Validation\AuthorizationException;
use Pinoox\Component\Validation\ValidationException;
use Illuminate\Validation\Validator;
use Pinoox\Component\Http\File\UploadedFile;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\InputBag;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\ServerBag;

abstract class FormRequest
{
    const AUTHORIZATION = 'authorization';
    const VALIDATION = 'validation';
    protected bool $stopOnFirstFailure = false;

    public Request $global;
    protected Validator $validator;

    protected bool $check = true;
    protected string $errorBag = 'default';
    public FileBag $files;
    public InputBag $cookies;
    public InputBag $request;
    public InputBag $query;
    public ServerBag $server;
    public HeaderBag $headers;
    public ParameterBag $parameters;

    public function __construct(Request $request)
    {
        $this->initialRequest($request);
        $this->validator = $this->createValidator();
    }

    protected function initialRequest(Request $request): void
    {
        $this->global = $request;
        $this->files = $request->files;
        $this->cookies = $request->cookies;
        $this->request = $request->request;
        $this->server = $request->server;
        $this->headers = $request->headers;
        $this->parameters = $request->parameters;
    }

    public function getPayload(): InputBag
    {
        return $this->global->getPayload();
    }

    public function validate($key = null, $default = null)
    {
        return data_get($this->validator->validate(), $key, $default);
    }

    public function safe(array $keys = null): ValidatedInput|array
    {
        return $this->validator->safe($keys);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function file(string $key, mixed $default = null): UploadedFile
    {
        return $this->global->file($key, $default);
    }

    public function store(string $key, $destination, $access = 'public', mixed $default = null): ?FileUploader
    {
        return $this->global->store($key, $destination, $access, $default);
    }

    public function validated($key = null, $default = null)
    {
        return data_get($this->validator->validated(), $key, $default);
    }

    protected function createValidator(): Validator
    {
        $this->prepend();
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

    public function remove(string $key): static
    {
        $this->input()->remove($key);

        return $this;
    }

    public function all(?string $key = null): array
    {
        return $this->input()->all($key);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->global->get($key, $default);
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

    /**
     * @throws \Illuminate\Validation\ValidationException
     * @throws AuthorizationException
     */
    public function __resolve(): void
    {
        if (!$this->check)
            return;

        $this->checkFormRequest();
    }

    /**
     * @throws \Illuminate\Validation\ValidationException
     * @throws AuthorizationException
     */
    protected function checkFormRequest(): void
    {
        if (!$this->passesAuthorization()) {
            $this->failedAuthorization();
        }

        if ($this->validator->fails()) {
            $this->failedValidation();
        }
    }

    /**
     * @throws \Illuminate\Validation\ValidationException
     * @throws AuthorizationException
     */
    public function check()
    {
        $this->checkFormRequest();
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

    protected function failedValidation()
    {
        $this->failed(self::VALIDATION);
        throw (new ValidationException($this->validator))
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
        return $this->validator->passes();
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
