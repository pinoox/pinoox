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

use Illuminate\Contracts\Translation\Translator;
use Illuminate\Validation\Validator;

class Validation extends Validator
{
    private array $mixinData = [];
    private array $replaceRules = [];

    public function __construct(Translator $translator, array $data, array $rules, array $messages = [], array $attributes = [])
    {
        parent::__construct($translator, $data, $rules, $messages, $attributes);
        $this->setException(ValidationException::class);
        $messages = array_merge($this->getDefaultMessages(), $messages);
        $this->setCustomMessages($messages);
    }

    public function getFallbackMessages(): array
    {
        return $this->fallbackMessages;
    }

    public function addReplaceRule($rule, $replace)
    {
        $this->replaceRules[$rule] = $replace;
    }

    protected function replaceRule($rule)
    {
        if (is_string($rule) && isset($this->replaceRules[$rule]))
            $rule = $this->replaceRules[$rule];

        return $rule;
    }

    protected function validateAttribute($attribute, $rule)
    {
        $rule = $this->replaceRule($rule);
        return parent::validateAttribute($attribute, $rule); // TODO: Change the autogenerated stub
    }

    public function getDefaultMessages(): array
    {
        $locales = [
            $this->translator->getLocale(),
            $this->translator->getFallback()
        ];

        foreach ($locales as $locale) {
            if ($messages = $this->getLocaleDefaultMessages($locale))
                return $messages;
        }
        return [];
    }

    private function getLocaleDefaultMessages(string $locale): bool|array
    {
        $file = $this->getFileDefaultMessages($locale);
        if (is_file($file)) {
            $result = require $file;
            return !empty($result) && is_array($result) ? $result : [];
        }

        return false;
    }

    private function getFileDefaultMessages(string $locale): string
    {
        return __DIR__ . '/messages/' . $locale . '.php';
    }

    public function validated()
    {
        return [
            ...parent::validated(),
            ...$this->getMixin(),
        ];
    }

    /**
     * @return array
     */
    public function getMixin(): array
    {
        return $this->mixinData;
    }

    /**
     * @param array $mixinData
     */
    public function mixin(array $mixinData): void
    {
        $this->mixinData = $mixinData;
    }
}
