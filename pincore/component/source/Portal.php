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


namespace pinoox\component\source;


use pinoox\component\Helpers\Str;
use pinoox\component\kernel\Container;
use pinoox\component\kernel\ContainerBuilder;
use SebastianBergmann\Type\ReflectionMapper;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

abstract class Portal
{
    protected static mixed $__lastHistory = null;
    protected static array $__history = [];
    protected static string $__method = '';
    protected static array $__args = [];
    protected static array $__subNameClasses = [];

    /*
    public function __get(string $name)
    {
        $instance = self::__instance();
        return $instance->$name;
    }

    public function __set(string $name, $value): void
    {
        $instance = self::__instance();
        $instance->$name = $value;
    }
    */

    /**
     * Check the builder method call
     *
     * @return bool
     */
    public static function __isCallBack(): bool
    {
        return true;
    }

    final protected function __method(): string
    {
        return self::$__method;
    }

    final public static function __rebuild()
    {
        $ids = static::__ids();
        foreach ($ids as $id) {
            static::__container()->removeDefinition($id);
        }

        static::__register();
    }

    final protected function __args($index = null): array
    {
        return is_null($index) ? self::$__args : @self::$__args[$index];
    }

    /**
     * Check the builder method call
     *
     * @return bool
     */
    public static function __isHistory(): bool
    {
        return false;
    }

    /**
     * register in container
     */
    public static function __register(): void
    {
    }

    /**
     * Get the registered name of the component.
     * @return string
     */
    abstract public static function __name(): string;

    /**
     * Get method list names for callback object.
     * @return string[]
     */
    public static function __callback(): array
    {
        return [];
    }

    /**
     * Get exclude method names.
     * @return string[]
     */
    public static function __exclude(): array
    {
        return [];
    }

    /**
     * Get include method names.
     * @return string[]
     */
    public static function __include(): array
    {
        return [];
    }

    /**
     * Get replace methods.
     * @return array
     */
    public static function __replace(): array
    {
        return [];
    }


    /**
     * Get compiled replace methods.
     * @return array
     */
    final public static function __compileReplaces(): array
    {
        $result = [];
        $replaceItems = static::__replace();
        foreach ($replaceItems as $methods => $closure) {

            $methods = Str::multiExplode(['|', ','], $methods);
            foreach ($methods as $method) {
                if (is_string($closure)) {
                    if (self::__has())
                        $closure = \Closure::fromCallable([static::__instance(), 'add']);
                    else
                        continue;
                }

                $method = trim($method);
                $result[$method] = $closure;
            }
        }

        return $result;
    }

    /**
     * call method
     *
     * @param string $method
     * @param array $args
     * @return mixed
     */
    protected static function callMethod(string $method, array $args): mixed
    {
        $instance = static::__instance();

        if (empty($instance) || self::checkMethodHasExclude($method) || !self::checkMethodHasInclude($method)) {
            throw new \RuntimeException('' . static::__id() . ' Portal root has not been set.');
        }

        self::$__method = $method;

        $isCallBack = false;
        if (static::__isCallBack()) {
            if (static::checkMethodHasCallback($method))
                $isCallBack = true;
        }

        $methods = static::__compileReplaces();
        if (isset($methods[$method])) {
            $result = $methods[$method](...$args);
        } else if (Str::firstHas($method, '___')) {
            $result = self::callSubClasses($method, $args);
        } else {
            $result = $instance->$method(...$args);
        }
        self::addHistory($method, $args, $result, $isCallBack);

        return $isCallBack ? new static() : $result;
    }

    private static function callSubClasses(string $method, array $args): mixed
    {
        $methodName = str_replace('___', '', $method);
        $instance = static::__instance();

        if (empty($methodName))
            return $instance;
        if (static::__has($methodName))
            return static::__instance($methodName);

        $name = Str::camelToUnderscore($methodName, '_');
        if (static::__has($name))
            return static::__instance($name);

        $name = Str::camelToUnderscore($methodName, '.');
        if (static::__has($name))
            return static::__instance($name);

        $name = Str::camelToUnderscore($methodName, '-');
        if (static::__has($name))
            return static::__instance($name);

        return $instance->$method(...$args);
    }

    /**
     * set history result
     *
     * @param string $method
     * @param array $args
     * @param mixed $result
     * @param bool $isCallBack
     */
    private static function addHistory(string $method, array $args, mixed $result, bool $isCallBack)
    {
        static::$__lastHistory = $result;
        if (static::__isHistory()) {
            static::$__history[] = [
                'method' => $method,
                'arguments' => $args,
                'result' => $result,
                'isCallback' => $isCallBack,
            ];
        }
    }


    /**
     * Handle dynamic, static calls to the object.
     *
     * @param string $method
     * @param array $args
     * @return mixed
     *
     * @throws \RuntimeException
     */
    public static function __callStatic(string $method, array $args): mixed
    {
        return static::callMethod($method, $args);
    }

    /**
     * Handle dynamic, calls to the object.
     *
     * @param string $method
     * @param array $args
     * @return mixed
     *
     * @throws \RuntimeException
     */
    public function __call(string $method, array $args): mixed
    {
        return static::callMethod($method, $args);
    }


    /**
     * Get the last result of calls to the object.
     *
     * @return mixed
     */
    final public static function __result(): mixed
    {
        return static::$__lastHistory;
    }

    /**
     * Get the result history of calls to the object.
     *
     * @param string|int|null $index
     * @return mixed
     */
    final public static function __history(string|int|null $index = null): mixed
    {
        return empty($index) ? static::$__history : static::$__history[$index];
    }

    /**
     * instance object in container
     *
     * @param string|null $name
     * @return object|null
     */
    final public static function __instance(?string $name = null): ?object
    {
        $name = static::__id($name);
        $container = self::__container();
        if (!empty($name) && $container->has($name)) {
            try {
                return $container->get($name);
            } catch (\Exception $e) {
            }
        }

        return null;
    }

    /**
     * instance object in container
     *
     * @param string|null $name
     * @return bool
     */
    final public static function __has(?string $name = null): bool
    {
        $name = static::__id($name);
        $container = self::__container();
        return !empty($name) && $container->has($name);
    }

    /**
     * instance object in container
     *
     * @param string|null $name
     * @return Definition|null
     */
    final public static function __definition(?string $name = null): ?Definition
    {
        $name = static::__id($name);
        $container = self::__container();
        if (!empty($name) && $container->hasDefinition($name)) {
            try {
                return $container->getDefinition($name);
            } catch (\Exception $e) {
            }
        }

        return null;
    }

    /**
     * get reference class
     * @param string|null $name
     * @return Reference
     */
    final public static function __ref(?string $name = null): Reference
    {
        $name = static::__id($name);
        return Container::ref($name);
    }

    /**
     * Check method has in callback list
     *
     * @param string $method
     * @return bool
     */
    private static function checkMethodHasCallback(string $method): bool
    {
        $methods = static::__callback();
        return in_array($method, $methods);
    }

    /**
     * Check method has in callback list
     *
     * @param string $method
     * @return bool
     */
    private static function checkMethodHasExclude(string $method): bool
    {
        $methods = static::__exclude();
        return in_array($method, $methods);
    }

    /**
     * Check method has in callback list
     *
     * @param string $method
     * @return bool
     */
    private static function checkMethodHasInclude(string $method): bool
    {
        $methods = static::__include();
        return empty($methods) || in_array($method, $methods);
    }

    /**
     * Check method return type is void
     *
     * @param string $method
     * @param null $instance
     * @return bool
     */
    private static function checkMethodIsVoid(string $method, $instance = null): bool
    {
        $returnType = null;
        $instance = !empty($instance) ? $instance : static::__instance();

        try {
            $method = new \ReflectionMethod($instance, $method);
            $returnType = (new ReflectionMapper)->fromReturnType($method);
            $returnType = !empty($returnType->asString()) ? $returnType->asString() : '';
        } catch (\ReflectionException $e) {
        }

        return $returnType === 'void';
    }

    /**
     * get container
     *
     * @return ContainerBuilder
     */
    final public static function __container(): ContainerBuilder
    {
        return Str::firstHas(static::class, 'pinoox\\app') ? Container::app() : Container::pincore();
    }

    /**
     * bind service
     *
     * @param string|object|null $class class object or class name
     * @param string|null $name
     * @return Definition|null
     */
    final public static function __bind(string|object|null $class = null, string $name = null): ?Definition
    {
        if (!empty($name) && !empty($class)) {
            static::$__subNameClasses[] = $name;
        }
        $id = static::__id($name);
        if (is_object($class)) {
            static::__container()->set($id, $class);
        } else {
            return static::__container()->register($id, $class);
        }
        return null;
    }

    /**
     * set parameter
     *
     * @param string $name
     * @param array|bool|string|int|float|null $value
     * @return void
     */
    final public static function __param(string $name, array|bool|string|int|float|null $value): void
    {
        self::__container()->setParameter($name, $value);
    }

    /**
     * get id
     *
     * @param string|null $name
     * @return string
     */
    final public static function __id(?string $name = null): string
    {
        return !empty($name) ? static::__name() . '.' . $name : static::__name();
    }

    /**
     * Returns an array of unique identifiers for the current class and its sub classes.
     *
     * @return string[] An array of unique identifiers.
     */
    final public static function __ids(): array
    {
        $ids = [static::__name()];
        foreach (static::$__subNameClasses as $name) {
            if (self::__has($name))
                $ids[] = static::__id($name);
        }

        return $ids;
    }

    final public static function __getSubNameClasses(): array
    {
        $names = [];
        foreach (static::$__subNameClasses as $name) {
            if (self::__has($name))
                $names[] = $name;
        }

        return $names;
    }
}