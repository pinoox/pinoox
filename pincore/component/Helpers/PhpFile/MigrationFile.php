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

use Illuminate\Database\Schema\Blueprint;
use Nette\PhpGenerator\ClassType;
use pinoox\component\File;
use pinoox\component\Helpers\Str;
use pinoox\component\migration\MigrationBase;

class MigrationFile extends PhpFile
{

    public static function create(string $exportPath, string $className, string $package, string $namespace): bool
    {
        $source = self::source();

        $namespace = str_replace(DS, '\\', $namespace);
        $namespace = $source->addNamespace($namespace);
        $namespace->addUse(Blueprint::class);
        $namespace->addUse(MigrationBase::class);

        $class = $namespace->addClass($className);
        $class->setExtends(MigrationBase::class);

        self::addRunMethod($class, $className, $package);
        self::addDownMethod($class, $className, $package);

        return File::generate($exportPath, $source);
    }

    private static function addRunMethod(ClassType $class, string $className, string $package): void
    {
        $tableName = Str::camelToUnderscore($className, '_');
        $method = $class->addMethod('up');
        $method->addComment('Run the migrations.');
        $method->setPublic()
            ->setReturnType('void')
            ->addBody('$this->schema->create("' . $package . '_' . $tableName . '", function (Blueprint $table) {')
            ->addBody("\t" . '$table->increments("id");' . "\n" . '});');
    }

    private static function addDownMethod(ClassType $class, string $className, string $package): void
    {
        $tableName = Str::camelToUnderscore($className, '_');
        $method = $class->addMethod('down');
        $method->addComment('Reverse the migrations.');
        $method->setPublic()
            ->setReturnType('void')
            ->addBody('$this->schema->dropIfExists("' . $package . '_' . $tableName . '");');
    }

}