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


namespace Pinoox\Component\Router;

use Closure;
use PhpParser\Node\Stmt\Else_;
use Pinoox\Component\Helpers\Str;
use Pinoox\Component\Package\App;

class Route
{
    public function __construct(
        private Collection           $collection,
        private string|array         $path,
        private array|string|Closure $action = '',
        private string               $name = '',
        private string|array         $methods = [],
        private array                $defaults = [],
        private array                $filters = [],
        private int                  $priority = 0,
        private string               $prefixName = '',
        public array                 $data = [],

    )
    {
        if ($this->path === '*') {
            $this->path = '{parameters}';
            $filters['parameters'] = '.*';
            $count = strlen($collection->path);
            $this->path = $this->getPath('');
            $this->priority = -9999 + $count;
        } else {
            $this->path = $this->getPath('/');
        }

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

    public function getData(): array
    {
        return $this->data;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * build name for route
     *
     * @param string $name
     * @return string
     */
    private function buildName(string $name = ''): string
    {
        return $this->prefixName . $name;
    }

    /**
     * @return string
     */
    public function getPath(string $separator = '/'): string
    {
        $prefixPath = (!empty($this->collection->prefixPath)) ? Str::lastDelete($this->collection->prefixPath, '/') : '';
        $path = Str::firstDelete($this->path, '/');
        return $prefixPath . $separator . $path;
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
}