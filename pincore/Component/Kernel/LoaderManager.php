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

/**
 * Class LoaderManager
 * @author   Vladimir Marchevsky
 * @link https://github.com/vladimmi/construct-static
 */
class LoaderManager

{
    const method = '__register';

    /**
     * Wrapped Composer object
     *
     * @var ClassLoader
     */
    private $loader;

    /**
     * Parameters to pass into constructors
     *
     * @var array
     */
    private $params = [];

    /**
     * Parameters to pass into constructors of specified class
     *
     * @var array
     */
    private $classParams = [];

    /**
     * Call static constructor for class if exists
     *
     * @param string $className
     */
    private function callConstruct($className)
    {
        if (!str_contains($className, 'Portal\\'))
            return;

        $reflectionClass = new \ReflectionClass($className);
        if ($reflectionClass->hasMethod(self::method)) {
            $reflectionMethod = $reflectionClass->getMethod(self::method);
            if ($reflectionMethod->isStatic() && $reflectionMethod->getDeclaringClass()->getName() === $className) {
                $reflectionMethod->setAccessible(true);
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
    public function __construct(ClassLoader $loader, $params = [])
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
     */
    public function loadClass($className)
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
    public function setClassParameters($className, $params)
    {
        $this->classParams[$className] = $params;
    }

    /**
     * Call static constructors on previously loaded classes
     */
    public function processLoadedClasses()
    {
        $classes = get_declared_classes();
        foreach ($classes as $className) {
            $this->callConstruct($className);
        }
    }
}