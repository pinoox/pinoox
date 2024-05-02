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


namespace Pinoox\Component\Helpers\PhpFile;

use Nette\PhpGenerator\ClassLike;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpFile as PhpFileNette;
use Nette\PhpGenerator\PhpNamespace;
use Pinoox\Component\File;
use Pinoox\Component\Helpers\HelperAnnotations;
use Pinoox\Component\Helpers\Str;
use Pinoox\Component\Kernel\Container;
use Pinoox\Component\Path\Path;
use Pinoox\Component\Source\Portal;
use Pinoox\Portal\App\AppEngine;
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
        $path = $this->portalFolder . '/' . $this->classname . '.php';
        $this->portalPath = $this->replaceDirectorySepartor($path, '/');
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

    private function replaceDirectorySepartor($path, $replace = '/')
    {
        return str_replace('\\', $replace, $path);
    }

    private function buildPackage($input): void
    {
        $package = $input->getOption('package');
        $this->package = !empty($package) ? $package : 'pincore';
    }

    private function buildNameSpace(): void
    {
        if ($this->package == 'pincore')
            $namespace = 'Pinoox\\Portal';
        else
            $namespace = 'App\\' . $this->package . '\\Portal';

        $subFolder = '';
        if (!empty($this->subFolder)) {
            $subFolder = $this->subFolder;

            $parts = explode('\\', $subFolder);
            foreach ($parts as $key => $part) {
                $parts[$key] = ucfirst($part);
            }

            $namespace .= '\\' . implode('\\', $parts);
        }

        $this->namespace = $namespace;
    }

    private function buildPortalName(): void
    {
        $this->portalName = $this->namespace . '\\' . $this->getClassname();
    }

    private function buildPortalFolder(): void
    {

        if ($this->package === 'pincore')
            $portalFolder = path('~pincore/Portal');
        else
            $portalFolder = path('Portal', $this->package);

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
                $serviceName = call_user_func([$portalName, '__id']);
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

        $args = self::getMethodParametersForDeclaration($method);
        $args = str_replace(["\n", "\r"], '', $args);
        $args = str_replace("array ()", '[]', $args);
        $args = rtrim($args);

        if ($return === $name) {
            $returnType = $className;
        } else if ($return === 'static') {
            $returnType = '\\' . $serviceName;
        } else if (!empty($return) && (class_exists($return) || interface_exists($return) || trait_exists($return))) {
            $returnType = Str::firstDelete($return, '\\');
            if ($use = self::getUse($namespace, $returnType)) {
                $returnType = $use;
            } else {
                $returnType = $this->buildAliasClassName($namespace, $num);
                $namespace->addUse($return, $returnType);
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
                    if (!Str::firstHas($part, '\\')) {
                        if (Str::firstHas($part, '?')) {
                            $part = str_replace('?', '?\\', $part);
                            $returnTypeParts[$index] = $part;
                        } else {
                            $returnTypeParts[$index] = '\\' . $part;
                        }
                    }
                }
            }

            $returnType = implode('|', $returnTypeParts);
        }


        $returnType = !empty($returnType) ? $returnType . ' ' : '';
        return "@method static {$returnType}{$methodName}({$args})";
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
        if ($this->package == 'pincore') {
            $container = Container::pincore();
        } else {
            $container = Container::app($this->package);
        }
        if ($container->hasDefinition($serviceName)) {
            $voidMethods = [];
            $num = 1;
            $definition = $container->getDefinition($serviceName);
            $className = $definition->getClass();
            $reflection = $container->getReflectionClass($className);
            $uses = HelperAnnotations::getUsesInPHPFile($className);

            $methods = [];

            $tags = HelperAnnotations::getTagsIntoComment($reflection->getDocComment());
            if (!empty($tags['mixin'])) {
                foreach ($tags['mixin'] as $mixinClassName) {
                    $mixinClassName = $uses[$mixinClassName] ?? $mixinClassName;
                    $reflectionMixinClass = new \ReflectionClass($mixinClassName);
                    $items = $reflectionMixinClass->getMethods(ReflectionMethod::IS_PUBLIC);
                    foreach ($items as $item) {
                        $methods[$item->getName()] = [
                            "method" => $item,
                            "class" => $mixinClassName,
                        ];
                    }
                }
            }

            $items = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);
            foreach ($items as $item) {
                $methods[$item->getName()] = [
                    "method" => $item,
                    "class" => $className,
                ];
            }

            foreach ($methods as $v) {
                /**
                 * @var ReflectionMethod $method
                 */
                $method = $v['method'];
                $_className = $v['class'];
                if (isset($replace[$method->getName()]) || Str::firstHas($method->getName(), '__') || in_array($method->getName(), $exclude) || (!empty($include) && !in_array($method->getName(), $include)) || method_exists($this->getPortalName(), $method->getName()))
                    continue;
                if ($method instanceof ReflectionMethod) {
                    $returnType = self::getReturnTypeMethod($method);
                    if ($returnType === 'void' && $isCallBack && empty($callback)) {
                        $voidMethods[] = $method->getName();
                    }

                    $class->addComment($this->generateMethodComment($this->getClassname(), $class->getName(), $_className, $method->getName(), $method, $namespace, $returnType, $isCallBack, $callback, $num));
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