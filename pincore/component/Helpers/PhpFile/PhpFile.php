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


namespace pinoox\component\Helpers\PhpFile;

use Illuminate\Database\Eloquent\Builder;
use Nette\PhpGenerator\PhpFile as PhpFileNette;
use Nette\PhpGenerator\PhpNamespace;
use PHPUnit\Framework\MockObject\ReflectionException;
use pinoox\component\File;
use pinoox\component\Helpers\HelperAnnotations;
use pinoox\component\Helpers\Str;
use ReflectionFunction;
use ReflectionMethod;
use SebastianBergmann\Type\ReflectionMapper;

class PhpFile
{
    public static function source(bool $isCopyright = true): PhpFileNette
    {
        $source = new PhpFileNette();
        if ($isCopyright)
            self::setCopyright($source);

        return $source;
    }

    private static function getMethodBody($path, ReflectionMethod $reflectionMethod)
    {
        $regex = '/function.*\s*?{(.*)\}/ms';
        $body = File::getBetweenLine($path, $reflectionMethod->getStartLine() - 1, $reflectionMethod->getEndLine());
        preg_match_all($regex, $body, $matches, PREG_SET_ORDER, 0);
        return $matches[0][1] ?? null;
    }

    private static function setCopyright(PhpFileNette $source): void
    {
        $copyright = '';
        $copyright .= "***  *  *     *  ****  ****  *    *\n";
        $copyright .= "  *  *  * *   *  *  *  *  *   *  *\n";
        $copyright .= "***  *  *  *  *  *  *  *  *    *\n";
        $copyright .= "     *  *   * *  *  *  *  *   *  *\n";
        $copyright .= "     *  *    **  ****  ****  *    *\n\n";
        $copyright .= sprintf("@author   %s\n", 'Pinoox');
        $copyright .= sprintf("@link %s\n", 'https://www.pinoox.com');
        $copyright .= sprintf("@license  %s\n", 'https://opensource.org/licenses/MIT MIT License');

        $source->setComment($copyright);
    }

    /**
     * @throws \ReflectionException
     */
    public static function getReturnTypeMethod(ReflectionMethod $method): string
    {
        $return = null;
        if (!$method->hasReturnType()) {
            $text = $method->getDocComment();
            $tags = [];
            if ($text) {
                $tags = HelperAnnotations::getTagsIntoComment($text);
            }
            if (!empty($tags['return'])) {
                $items = explode('|', $tags['return']);
                $uses = HelperAnnotations::getUsesInPHPFile($method->class);
                $returns = [];
                foreach ($items as $item) {
                    if ($item === 'void')
                        continue;

                    $basename = basename(str_replace('\\', '/', $method->class));
                    $className = '\\'.$method->class;
                    if (Str::firstHas($item,'static'))
                        $returns[] = str_replace('static',$className,$item);
                    else if (Str::firstHas($item,'$this'))
                        $returns[] = str_replace('$this',$className,$item);
                    else if (Str::firstHas($item,$basename))
                        $returns[] = str_replace($basename,$className,$item);
                    else {
                        $returns[] = !empty($uses[$item]) ? '\\'.$uses[$item] : $item;
                    }
                }
                $return = implode('|', $returns);
            }
        } else {
            $return = (new ReflectionMapper)->fromReturnType($method);
            $return = !empty($return->asString()) ? $return->asString() : '';
        }

        return $return ?? '';
    }

    private static function hasUse(PhpNamespace $namespace, $class): bool
    {
        $uses = $namespace->getUses();
        return in_array($class, $uses);
    }

    protected static function getUse(PhpNamespace $namespace, $class): bool|int|string
    {
        $uses = $namespace->getUses();
        return array_search($class, $uses);
    }

    public static function getMethodParametersForDeclaration(ReflectionFunction|ReflectionMethod|\Closure $method): string
    {
        if ($method instanceof \Closure) {
            try {
                $method = new ReflectionFunction($method);
            } catch (\ReflectionException $e) {
                return '';
            }
        }

        $parameters = [];
        $types = (new ReflectionMapper)->fromParameterTypes($method);

        foreach ($method->getParameters() as $i => $parameter) {
            $name = '$' . $parameter->getName();

            /* Note: PHP extensions may use empty names for reference arguments
             * or "..." for methods taking a variable number of arguments.
             */
            if ($name === '$' || $name === '$...') {
                $name = '$arg' . $i;
            }

            $default = '';
            $reference = '';
            $typeDeclaration = '';

            if (!$types[$i]->type()->isUnknown()) {
                $typesString = $types[$i]->type()->asString();
                $typesItems = explode('|', $typesString);
                foreach ($typesItems as $key => $typesItem) {
                    if (class_exists($typesItem) || interface_exists($typesItem) || trait_exists($typesItem))
                        $typesItems[$key] = '\\' . $typesItem;
                }
                $typesString = implode('|', $typesItems);
                $typeDeclaration = $typesString . ' ';
            }

            if ($parameter->isPassedByReference()) {
                $reference = '&';
            }

            if ($parameter->isVariadic()) {
                $name = '...' . $name;
            } elseif ($parameter->isDefaultValueAvailable()) {
                $default = ' = ' . self::exportDefaultValueParameterMethod($parameter);
            } elseif ($parameter->isOptional()) {
                $default = ' = null';
            }

            $parameters[] = $typeDeclaration . $reference . $name . $default;
        }

        return implode(', ', $parameters);
    }

    public static function exportDefaultValueParameterMethod(\ReflectionParameter $parameter): string
    {
        try {
            $defaultValue = $parameter->getDefaultValue();

            if (!is_object($defaultValue)) {
                return (string)var_export($defaultValue, true);
            }

            $parameterAsString = $parameter->__toString();
            return (string)explode(
                ' = ',
                substr(
                    substr(
                        $parameterAsString,
                        strpos($parameterAsString, '<optional> ') + strlen('<optional> ')
                    ),
                    0,
                    -2
                )
            )[1];
            // @codeCoverageIgnoreStart
        } catch (\ReflectionException $e) {
            throw new ReflectionException(
                $e->getMessage(),
                $e->getCode(),
                $e
            );
        }
        // @codeCoverageIgnoreEnd
    }

    public static function getMethodParametersForCall(ReflectionMethod $method): string
    {
        $parameters = [];

        foreach ($method->getParameters() as $i => $parameter) {
            $name = '$' . $parameter->getName();

            /* Note: PHP extensions may use empty names for reference arguments
             * or "..." for methods taking a variable number of arguments.
             */
            if ($name === '$' || $name === '$...') {
                $name = '$arg' . $i;
            }

            if ($parameter->isVariadic()) {
                continue;
            }

            if ($parameter->isPassedByReference()) {
                $parameters[] = '&' . $name;
            } else {
                $parameters[] = $name;
            }
        }

        return implode(', ', $parameters);
    }


}