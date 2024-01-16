<?php

/**
 * ***  *  *     *  ****  ****  *    *
 *   *  *  * *   *  *  *  *  *   *  *
 * ***  *  *  *  *  *  *  *  *    *
 *      *  *   * *  *  *  *  *   *  *
 *      *  *    **  ****  ****  *    *
 *
 * @author   Pinoox
 * @link https://www.pinoox.com
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace Pinoox\Portal;

use Illuminate\Contracts\Translation\Translator as ObjectPortal2;
use Illuminate\Validation\DatabasePresenceVerifier;
use Illuminate\Validation\Factory as ObjectPortal4;
use Illuminate\Validation\PresenceVerifierInterface as ObjectPortal3;
use Illuminate\Validation\Validator as ObjectPortal1;
use Pinoox\Component\Source\Portal;
use Pinoox\Component\Validation\Factory;

/**
 * @method static ObjectPortal1 make(array $data, array $rules, array $messages = [], array $attributes = [])
 * @method static array validate(array $data, array $rules, array $messages = [], array $attributes = [])
 * @method static extend($rule, $extension, $message = NULL)
 * @method static extendImplicit($rule, $extension, $message = NULL)
 * @method static extendDependent($rule, $extension, $message = NULL)
 * @method static replacer($rule, $replacer)
 * @method static includeUnvalidatedArrayKeys()
 * @method static excludeUnvalidatedArrayKeys()
 * @method static resolver(\Closure $resolver)
 * @method static ObjectPortal2 getTranslator()
 * @method static ObjectPortal3 getPresenceVerifier()
 * @method static setPresenceVerifier(\Illuminate\Validation\PresenceVerifierInterface $presenceVerifier)
 * @method static \Illuminate\Contracts\Container\Container|null getContainer()
 * @method static ObjectPortal4 setContainer(\Illuminate\Contracts\Container\Container $container)
 * @method static \Illuminate\Validation\DatabasePresenceVerifier ___verifier()
 * @method static \Pinoox\Component\Validation\Factory ___()
 *
 * @see \Pinoox\Component\Validation\Factory
 */
class Validation extends Portal
{
	public static function __register(): void
	{
		self::__bind(DatabasePresenceVerifier::class, 'verifier')->setArguments([
		    DB::getDatabaseManager(),
		]);
		self::__bind(Factory::class)->setArguments([
		    Lang::__ref()
		])->addMethodCall('setPresenceVerifier', [
		    self::__ref('verifier'),
		]);
	}


	/**
	 * Get the registered name of the component.
	 * @return string
	 */
	public static function __name(): string
	{
		return 'validation';
	}


	/**
	 * Get exclude method names .
	 * @return string[]
	 */
	public static function __exclude(): array
	{
		return [];
	}


	/**
	 * Get method names for callback object.
	 * @return string[]
	 */
	public static function __callback(): array
	{
		return [];
	}
}
