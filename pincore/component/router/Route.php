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


namespace pinoox\component\router;

use Closure;
use pinoox\component\helpers\Str;
use pinoox\component\package\App;

class Route
{
    private const FOR_ROUTE = 'route';

    /**
     * all route names
     *
     * @var array
     */
    public static array $names = [];

    public function __construct(
        private Collection           $collection,
        private string|array         $path,
        private array|string|Closure $action = '',
        private string               $name = '',
        private string|array         $methods = [],
        private array                $defaults = [],
        private array                $filters = [],
        private string               $prefixName = ''
    )
    {
        $this->name = $this->buildName($name);
        $this->defaults = array_merge($this->collection->defaults, $defaults);
        $this->filters = array_merge($this->collection->filters, $filters);
        $this->defaults['_controller'] = $action;
        $actionCollection = $this->collection->action;
        if (!empty($actionCollection)) {
            if (!empty($action))
                $this->defaults['_action_collection'] = $actionCollection;
            else
                $this->defaults['_controller'] = $actionCollection;
        }

        $this->methods = $this->collection->buildMethods($methods);
        $this->defaults['_router'] = $this;
    }

    public function get(): RouteCapsule
    {
        $route = new RouteCapsule($this->path, $this->defaults);
        $route->setMethods($this->methods);
        $route->setRequirements($this->filters);
        return $route;
    }

    public function countAll(): int
    {
        return count(self::$names);
    }

    /**
     * build name for route
     *
     * @param string $name
     * @return string
     */
    private function buildName(string $name = ''): string
    {
        $prefix = $this->collection->name;
        $prefix =  $this->prefixName. $prefix;
        $name = !empty($name) ? $name : self::generateRandomName($prefix);
        $name = $prefix . $name;
        self::$names[$prefix . $name] = $this;

        return $name;
    }

    /**
     * get all names
     *
     * @return array
     */
    public function all(): array
    {
        return self::$names;
    }


    /**
     * @return string
     */
    public function getPath(): string
    {
        $prefixPath = (!empty($this->collection->prefixPath)) ? Str::lastDelete($this->collection->prefixPath, '/') : '';
        $path = Str::firstDelete($this->path, '/');
        return $prefixPath . '/' . $path;
    }

    /**
     * @return array
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * @return Collection
     */
    public function getCollection(): Collection
    {
        return $this->collection;
    }

    /**
     * @return array|Closure|string
     */
    public function getAction(): array|string|Closure
    {
        return $this->action;
    }

    /**
     * @param array|Closure|string $action
     */
    public function setAction(array|string|Closure $action): void
    {
        $this->action = $action;
    }

    /**
     * @return array
     */
    public function getDefaults(): array
    {
        return $this->defaults;
    }

    /**
     * @return array|string
     */
    public function getMethods(): array|string
    {
        return $this->methods;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * generate random name
     *
     * @param string $prefix
     * @return string
     */
    public static function generateRandomName(string $prefix = ''): string
    {
        $name = self::FOR_ROUTE . '_' . count(self::$names);
        if (isset(self::$names[$prefix . $name])) {
            $name = self::FOR_ROUTE . '_' . Str::generateLowRandom(8);
        }
        return $name;
    }
}