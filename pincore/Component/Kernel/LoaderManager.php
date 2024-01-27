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

namespace Pinoox\Component\Kernel;

use Composer\Autoload\ClassLoader;
use Pinoox\Component\Database\Model;

/**
 * Class LoaderManager
 * @author   Vladimir Marchevsky
 * @link https://github.com/vladimmi/construct-static
 */
class LoaderManager

{
    const method = '__register';
    const classes = [
        'Portal\\',
        'Pinoox\Component\Kernel\BootInterface'
    ];

    /**
     * Wrapped Composer object
     *
     * @var ClassLoader
     */
    private ClassLoader $loader;

    /**
     * Parameters to pass into constructors
     *
     * @var array
     */
    private array $params = [];

    /**
     * Parameters to pass into constructors of specified class
     *
     * @var array
     */
    private array $classParams = [];

    /**
     * Call static constructor for class if exists
     *
     * @param string $className
     * @throws \ReflectionException
     */
    private function callConstruct(string $className): void
    {
        $classes = array_filter(self::classes, function ($class) use ($className) {
            return ($className !== $class) && (str_contains($className, $class) || is_subclass_of($className, $class));
        });

        if (empty($classes))
            return;

        $reflectionClass = new \ReflectionClass($className);
        if ($reflectionClass->hasMethod(self::method)) {
            $reflectionMethod = $reflectionClass->getMethod(self::method);
            if ($reflectionMethod->isStatic() && $reflectionMethod->getDeclaringClass()->getName() === $className) {
                $reflectionParams = $reflectionMethod->getParameters();
                if (count($reflectionParams) > 0) {
                    if (isset($this->classParams[$className])) {
                        //Pass custom specified parameters
                        $reflectionMethod->invoke(null, $this->classParams[$className]);
                    } else {
                        //Pass common parameters
                        $reflectionMethod->invoke(null, $this->params);
                    }
                } else {
                    $reflectionMethod->invoke(null);
                }
            }
        }
    }

    /**
     * @param ClassLoader $loader Composer loader object
     * @param array $params Additional parameters to pass into constructors, like DI container, etc
     */
    public function __construct(ClassLoader $loader, array $params = [])
    {
        $this->loader = $loader;
        $this->params = $params;

        //unregister composer
        $loaders = spl_autoload_functions();
        foreach ($loaders as $l) {
            // we need to replace only composer
            if (is_array($l) && $l[0] instanceof ClassLoader) {
                spl_autoload_unregister($l);
            }
        }

        //register wrapper
        spl_autoload_register([$this, 'loadClass'], true, true);
    }

    /**
     * Loads the given class or interface and invokes static constructor on it
     *
     * @param string $className The name of the class
     * @return bool|null True if loaded, null otherwise
     * @throws \ReflectionException
     */
    public function loadClass($className): ?bool
    {
        $result = $this->loader->loadClass($className);
        if ($result === true) {
            //class loaded successfully
            $this->callConstruct($className);
            return true;
        }
        return null;
    }

    /**
     * Set parameters to pass into specified class instead of default ones
     *
     * @param string $className
     * @param array $params
     */
    public function setClassParameters($className, $params): void
    {
        $this->classParams[$className] = $params;
    }

    /**
     * Call static constructors on previously loaded classes
     * @throws \ReflectionException
     */
    public function processLoadedClasses(): void
    {
        $classes = get_declared_classes();
        foreach ($classes as $className) {
            $this->callConstruct($className);
        }
    }
}