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


namespace pinoox\component\helpers\PhpFile;

use Nette\PhpGenerator\ClassLike;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile as PhpFileNette;
use Nette\PhpGenerator\PhpNamespace;
use pinoox\component\File;
use pinoox\component\helpers\HelperString;
use pinoox\component\helpers\Str;
use pinoox\component\kernel\Container;
use pinoox\component\package\App;
use pinoox\component\source\Portal;
use pinoox\portal\AppManager;
use Symfony\Component\Console\Input\InputInterface;
use ReflectionFunction;
use ReflectionMethod;

class PortalFile extends PhpFile
{
    private string $classname;
    private string $portalName;
    private string $subFolder;
    private string $service;
    private string $package;
    private string $namespace;
    private array $app;
    private string $portalFolder;
    private string $portalPath;

    public function __construct(private InputInterface $input, $isService = true)
    {
        $this->buildClassname($input);
        if ($isService)
            $this->buildService($input);
        $this->buildPackage($input);
        $this->buildNameSpace();
        $this->buildPortalName();
        $this->buildPortalFolder();
        $this->buildPortalPath();
    }

    private function buildPortalPath(): void
    {
        $path = $this->portalFolder . '\\' . $this->classname . '.php';
        $this->portalPath = $this->replaceDirectorySepartor($path, DS);
    }


    private function buildClassname($input): void
    {
        $className = $input->getArgument('portalName');

        $parts = Str::multiExplode(['/', '>', '\\'], $className);
        $className = array_pop($parts);
        $this->subFolder = implode('\\', $parts);

        $className = Str::toCamelCase($className);
        $this->classname = $className;
    }

    private function buildService($input): void
    {
        $service = $input->getOption('service');
        if (empty($service)) {
            $service = lcfirst($this->classname);

            if (!empty($this->subFolder)) {
                $parent = $this->replaceDirectorySepartor($this->subFolder, '.');
                $service = $parent . '.' . $service;
            }
        }
        $this->service = $service;
    }

    private function replaceDirectorySepartor($path, $replace = DS)
    {
        return str_replace(['\\', '/', '>'], $replace, $path);
    }

    private function buildPackage($input): void
    {
        $this->package = $input->getOption('package');
        $package = $input->getOption('package');
        $this->app = AppManager::getApp($package);

        $this->package = $this->app['package'];
    }

    private function buildNameSpace(): void
    {
        $namespace = $this->app['namespace'] . '\\' . 'portal';
        if (!empty($this->subFolder)) {
            $namespace .= '\\' . $this->subFolder;
        }
        $this->namespace = $namespace;
    }

    private function buildPortalName(): void
    {
        $this->portalName = $this->namespace . '\\' . $this->getClassname();
    }

    private function buildPortalFolder(): void
    {
        $portalFolder = $this->app['path'] . 'portal';
        if (!empty($this->subFolder)) {
            $portalFolder .= '/' . $this->subFolder;
        }
        $this->portalFolder = $this->replaceDirectorySepartor($portalFolder);
    }

    /**
     * @return string
     */
    public function getClassname(): string
    {
        return $this->classname;
    }

    /**
     * @return string
     */
    public function getPortalName(): string
    {
        return $this->portalName;
    }

    /**
     * @return string
     */
    public function getNamespace(): string
    {
        return $this->namespace;
    }

    /**
     * @return array
     */
    public function getApp(): array
    {
        return $this->app;
    }

    /**
     * @return string
     */
    public function getPackage(): string
    {
        return $this->package;
    }

    /**
     * @return string
     */
    public function getPortalFolder(): string
    {
        return $this->portalFolder;
    }

    /**
     * @return string
     */
    public function getPortalPath(): string
    {
        return $this->portalPath;
    }

    /**
     * @return string
     */
    public function getService(): string
    {
        return $this->service;
    }

    /**
     * @return string
     */
    public function getSubFolder(): string
    {
        return $this->subFolder;
    }

    public function create(): bool
    {
        $source = self::source();

        $namespace = $source->addNamespace($this->getNamespace());
        $namespace->addUse(Portal::class);

        $class = $namespace->addClass($this->getClassname());
        $class->setExtends(Portal::class);
        $this->addMethodName($class, $this->getClassname());
        $this->addMethodCallBack($class);
        $this->addMethodExclude($class);

        $this->addCommentMethods($class, $namespace);
        return File::generate($this->getPortalPath(), $source);
    }

    public function update(): bool
    {
        $source = PhpFileNette::fromCode(file_get_contents($this->getPortalPath()));

        $namespaceItems = array_values($source->getNamespaces());
        $namespace = $namespaceItems[0];
        $classes = $namespace->getClasses();
        foreach ($classes as $class) {
            $portalName = $namespace->getName() . '\\' . $class->getName();
            if ($class->getName() === $this->getClassname()) {
                $serviceName = call_user_func([$portalName, '__name']);
                $class->setComment('');
                $this->addCommentMethods($class, $namespaceItems[0], $serviceName);
            }
        }

        return File::generate($this->getPortalPath(), $source);
    }

    private function addMethodExclude(ClassType $class): void
    {
        if (!$class->hasMethod('__exclude')) {
            $method = $class->addMethod('__exclude');
            $method->addComment('Get exclude method names .');
            $method->addComment('@return string[]');
            $method->setPublic()
                ->setStatic()
                ->setReturnType('array')
                ->addBody("return [];");
        }
    }

    private function addMethodCallBack(ClassType $class, array $items = []): void
    {
        if (!empty($items)) {
            $items = implode("',\n\t'", $items);
            $body = "return [\n\t'" . $items . "'\t\n];";
        } else {
            $body = "return [];";
        }


        if ($class->hasMethod('__callback')) {
            $class->removeMethod('__callback');
        }

        $method = $class->addMethod('__callback');
        $method->addComment('Get method names for callback object.');
        $method->addComment('@return string[]');
        $method->setPublic()
            ->setStatic()
            ->setReturnType('array')
            ->addBody($body);
    }

    private function addMethodName(ClassType $class, $serviceName): void
    {
        $serviceName = Str::camelToUnderscore($serviceName, '.');
        $method = $class->addMethod('__name');
        $method->addComment('Get the registered name of the component.');
        $method->addComment('@return string');
        $method->setPublic()
            ->setStatic()
            ->setReturnType('string')
            ->addBody("return '{$serviceName}';");
    }

    private function addMethodRegisterInPortal(ClassType $class, string $body): void
    {
        // add method in class
        $method = $class->addMethod('__register');
        $method->addComment('register component.');
        $method->setPublic()
            ->setStatic()
            ->setReturnType('void')
            ->addBody($body);
    }

    private function buildAliasClassName(PhpNamespace $namespace, &$num)
    {
        $uses = $namespace->getUses();
        $returnType = 'ObjectPortal' . $num;
        $num++;
        if (isset($uses[$returnType]))
            return $this->buildAliasClassName($namespace, $num);

        return $returnType;
    }

    public function generateMethodComment(string $name, string $className, string $serviceName, string $methodName, ReflectionFunction|ReflectionMethod $method, PhpNamespace $namespace, string $return, bool $isCallBack = true, array $callback = [], int &$num = 1): string
    {

        if (($return === 'void' || in_array($methodName, $callback)) && $isCallBack) {
            $return = $className;
        }

        $args = str_replace("\n", '', self::getMethodParametersForDeclaration($method));
        $args = str_replace("array ()", '[]', $args);
        if ($return === $name) {
            $returnType = $className;
        } else if ($return === 'static') {
            $returnType = '\\' . $serviceName;
        } else if (!empty($return) && (class_exists($return) || interface_exists($return) || trait_exists($return))) {
            if ($use = self::getUse($namespace, $return)) {
                $returnType = $use;
            } else {
                $returnType = $this->buildAliasClassName($namespace,$num);
                $namespace->addUse($return,$returnType);
            }

        } else {
            $returnType = $return;
        }
        if (Str::has($returnType, '?')) {
            $returnType = str_replace('?', '', $returnType);
            if (!Str::has($returnType, 'null') || !Str::has($returnType, 'NULL')) {
                $returnType .= '|null';
            }
        }

        if (!empty($returnType)) {
            $returnTypeParts = explode('|', $returnType);
            foreach ($returnTypeParts as $index => $part) {
                if ((class_exists($part) || interface_exists($part) || trait_exists($part))) {
                    if (!Str::firstHas($part, '\\'))
                        $returnTypeParts[$index] = '\\' . $part;
                }
            }

            $returnType = implode('|', $returnTypeParts);
        }


        $returnType = !empty($returnType) ? $returnType . ' ' : '';
        return HelperString::replaceData('@method static {return}{name}({args})', [
            'name' => $methodName,
            'return' => $returnType,
            'args' => $args,
        ]);
    }

    public function addCommentMethods(ClassType|ClassLike $class, PhpNamespace $namespace, string $serviceName = null): void
    {

        $serviceName = !empty($serviceName) ? $serviceName : $this->getService();
        $isCallBack = true;
        $callback = [];
        $include = [];
        $exclude = [];
        $replace = [];

        if (class_exists($this->getPortalName())) {
            $isCallBack = call_user_func([$this->getPortalName(), '__isCallBack']);
            $callback = call_user_func([$this->getPortalName(), '__callback']);
            $exclude = call_user_func([$this->getPortalName(), '__exclude']);
            $include = call_user_func([$this->getPortalName(), '__include']);
            $replace = call_user_func([$this->getPortalName(), '__compileReplaces']);
        }
        $container = Container::pincore();

        // TODO app container
//        if (App::exists($this->getPackage())) {
//            $container = Container::app($this->getPackage());
//        } else {
//            $container = Container::pincore();
//        }
        if ($container->hasDefinition($serviceName)) {
            $voidMethods = [];
            $num = 1;
            $className = $container->getDefinition($serviceName)->getClass();
            $reflection = $container->getReflectionClass($className);
            $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
            foreach ($methods as $method) {
                if (isset($replace[$method->getName()]) || HelperString::firstHas($method->getName(), '__') || in_array($method->getName(), $exclude) || (!empty($include) && !in_array($method->getName(), $include)) || method_exists($this->getPortalName(), $method->getName()))
                    continue;
                if ($method instanceof ReflectionMethod) {
                    $returnType = self::getReturnTypeMethod($method);
                    if ($returnType === 'void' && $isCallBack && empty($callback)) {
                        $voidMethods[] = $method->getName();
                    }
                    $class->addComment($this->generateMethodComment($this->getClassname(), $class->getName(), $className, $method->getName(), $method, $namespace, $returnType, $isCallBack, $callback, $num));
                }
            }

            foreach ($replace as $methodName => $closure) {
                try {
                    $func = new ReflectionFunction($closure);
                } catch (\ReflectionException $e) {
                    continue;
                }


                if (in_array($methodName, $exclude))
                    continue;
                $returnType = $this->getReturnTypeMethod($func);
                if ($returnType === 'void' && $isCallBack && empty($callback)) {
                    $voidMethods[] = $methodName;
                }
                $class->addComment($this->generateMethodComment($this->getClassname(), $class->getName(), $className, $methodName, $func, $namespace, $returnType, $isCallBack, $callback, $num));
            }

            if (empty($callback))
                $this->addMethodCallBack($class, $voidMethods);

            $this->addCommentForSubClasses($container, $class);
            $class->addComment('@method static \\' . $className . ' ___()');
            $class->addComment("\n" . '@see \\' . $className);
        }
    }

    private function addCommentForSubClasses($container, $class): void
    {
        if (!class_exists($this->getPortalName()))
            return;

        $name = call_user_func([$this->getPortalName(), '__name']);
        $subClasses = call_user_func([$this->getPortalName(), '__getSubNameClasses']);
        array_unshift($subClasses);

        if (empty($subClasses))
            return;

        foreach ($subClasses as $subClass) {
            $id = $name . '.' . $subClass;
            if ($container->hasDefinition($id)) {
                $className = $container->getDefinition($id)->getClass();
                $methodName = Str::camelCase($subClass, ['-', '_', '.']);
                $class->addComment('@method static \\' . $className . ' ___' . $methodName . '()');
            }
        }
    }
}