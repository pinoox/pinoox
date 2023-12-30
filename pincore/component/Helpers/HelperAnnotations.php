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


namespace Pinoox\Component\Helpers;


use Illuminate\Database\Eloquent\Builder;
use Pinoox\Component\File;
use ReflectionClass;

class HelperAnnotations
{
    /**
     * Get Tags Current Block in a file
     *
     * @param string $file
     * @return array
     */
    public static function getTagsCurrentBlockInFile(string $file): array
    {
        $tags = [];
        if (is_file($file)) {
            $comments = self::getDocBlocks($file);
            $_to_string = trim(current($comments), "\**/");
            if (\preg_match_all('/@(?P<name>[A-Za-z_-]+)(?:[ \t]+(?P<value>.*?))?[ \t]*\r?$/m', $_to_string, $matches)) {
                $numMatches = \count($matches[0]);
                for ($i = 0; $i < $numMatches; ++$i) {
                    $tags[$matches['name'][$i]] = (string)$matches['value'][$i];
                }
            }
        }

        return $tags;
    }

    /**
     * @throws \ReflectionException
     */
    public static function getUsesInPHPFile(ReflectionClass|string $class): array
    {
        if (is_string($class)) {
            $class = new ReflectionClass($class);
        }
        $regex = '/^use\s+(?P<class>[\w\\\\]+)(?:\s+as\s+(?P<alias>\w+))?;/m';
        $source = file_get_contents($class->getFileName());
        preg_match_all($regex, $source, $matches, PREG_SET_ORDER, 0);
        $result = [];
        foreach ($matches as $match) {
            $usedClass = $match['class'];
            $className = $match['alias'] ?? basename(str_replace('\\', '/', $match['class']));
            $result[$className] = $usedClass;
        }

        return $result;
    }

    public static function getTagsIntoComment(string $text): array
    {
        $tags = [];
        $_to_string = trim($text, "\**/");
        if (\preg_match_all('/@(?P<name>[A-Za-z_-]+)(?:[ \t]+(?P<value>.*?))?[ \t]*\r?$/m', $_to_string, $matches)) {
            $numMatches = \count($matches[0]);
            for ($i = 0; $i < $numMatches; ++$i) {
                $tags[$matches['name'][$i]] = (string)$matches['value'][$i];
            }
        }

        return $tags;
    }


    /**
     * Get comments in a file
     *
     * @param string $file
     * @return array
     */
    public static function getDocBlocks(string $file): array
    {
        $comments = [];
        if (is_file($file)) {
            $tokens = token_get_all(file_get_contents($file));
            $comments = array();
            foreach ($tokens as $token) {
                if ($token[0] == T_DOC_COMMENT) {
                    $comments[] = $token[1];
                }
            }
        }
        return $comments;
    }

    /**
     * Generate DocBlock
     *
     * @param string $description
     * @param array $tags
     * @param string $preLine
     * @return string
     */
    public static function generateDocBlock(string $description, array $tags = [], string $preLine = ''): string
    {
        $result = $preLine . '/**' . "\n";
        $result .= $preLine . ' * ' . $description . "\n";

        foreach ($tags as $key => $value) {
            $result .= $preLine . ' * @' . $key . ' ' . $value . "\n";
        }

        $result .= $preLine . ' */';

        return $result;
    }
}