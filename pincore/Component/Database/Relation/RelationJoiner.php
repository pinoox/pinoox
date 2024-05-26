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


namespace Pinoox\Component\Database\Relation;


use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Expression;

class RelationJoiner
{
    protected Builder $query;
    protected string|array $tablePrefix;

    public function __construct(Builder $query, string|array $tablePrefix = [])
    {
        $this->query = $query;
        $this->tablePrefix = $tablePrefix;
    }

    protected function getAliasesJoin(): array
    {
        $aliases = [];
        foreach ($this->query->joins as $join) {
            if ($alias = $this->getAlias($join->table))
                $aliases[] = $alias;
        }

        return $aliases;
    }

    protected function generateAlias($table = null): string
    {
        $table = $table ?? $this->tableName();
        $tablePrefix = is_array($this->tablePrefix) ? $this->tablePrefix : [$this->tablePrefix];
        foreach ($tablePrefix as &$prefix) {
            $prefix .= '_';
        }
        return str_replace($tablePrefix, '', $table);
    }


    protected function getAlias($table = null)
    {
        $table = $table ?? $this->tableName();
        if ($table instanceof Expression)
            $table = $table->getValue($this->query->getGrammar());

        if (!is_string($table))
            return null;

        $table = str_replace([' as ', ' AS '], '|', $table);
        $alias = null;
        if (str_contains($table, '|')) {
            $alias = explode('|', $table);
            $alias = array_pop($alias);
        }

        if (!$alias)
            return null;
        $alias = strtolower($alias);
        return str_replace(['"', "'", '`'], '', $alias);
    }

    protected function tableName()
    {
        return $this->query->getModel()->getTable();
    }

    protected function hasAliasInJoin($alias): bool
    {
        $aliases = $this->getAliasesJoin();
        return in_array($alias, $aliases);
    }

    public function joinWith(string|array $relations, $type = 'inner', $where = false, $query = null):
    Builder
    {
        $query = $query ?? $this->query;

        if (is_array($relations)) {
            foreach ($relations as $relation => $itemType) {
                $typeItem = !is_numeric($relation) ? $itemType : $type;
                $relation = !is_numeric($relation) ? $relation : $itemType;
                $query = $this->joinWith($relation, $typeItem, $where, $query);
            }
            return $query;
        }

        $relationObj = $this->query->getRelation($relations);
        $alias = $this->getAlias($this->query->from);
        $table = $this->tableName();
        if (!$alias) {
            $alias = $this->alias ?? $this->generateAlias();
            $query = $query->from($table, $alias);
        }

        $relationAlias = $this->getAlias($relationObj->getQuery()->from);

        if (!$relationAlias) {
            $relationAlias = $relationObj->getModel()->alias ?? $relations;
        }

        $foreignKey = $relationObj->getForeignKeyName();
        $ownerKey = $relationObj->getOwnerKeyName() ?? $foreignKey;
        return $query->joinSub($relationObj->getQuery(), $relationAlias, $alias . '.' . $foreignKey, '=', $relationAlias . '.' . $ownerKey, $type, $where);
    }
}