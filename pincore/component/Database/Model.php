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

namespace pinoox\component\database;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model as EloquentModel;

use Closure as ObjectPortal11;
use Illuminate\Contracts\Pagination\CursorPaginator as ObjectPortal9;
use Illuminate\Contracts\Pagination\LengthAwarePaginator as ObjectPortal7;
use Illuminate\Contracts\Pagination\Paginator as ObjectPortal8;
use Illuminate\Database\Eloquent\Builder as ObjectPortal1;
use Illuminate\Database\Eloquent\Collection as ObjectPortal2;
use Illuminate\Database\Eloquent\Model as ObjectPortal3;
use Illuminate\Database\Eloquent\Relations\Relation as ObjectPortal4;
use Illuminate\Database\Query\Builder as ObjectPortal10;
use Illuminate\Support\Collection as ObjectPortal6;
use Illuminate\Support\LazyCollection as ObjectPortal5;

/**
 * @method static \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder make(array $attributes = [])
 * @method static ObjectPortal1 withGlobalScope($identifier, $scope)
 * @method static ObjectPortal1 withoutGlobalScope($scope)
 * @method static ObjectPortal1 withoutGlobalScopes(?array $scopes = NULL)
 * @method static array removedScopes()
 * @method static ObjectPortal1 whereKey($id)
 * @method static ObjectPortal1 whereKeyNot($id)
 * @method static ObjectPortal1 where($column, $operator = NULL, $value = NULL, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder|null firstWhere($column, $operator = NULL, $value = NULL, $boolean = 'and')
 * @method static ObjectPortal1 orWhere($column, $operator = NULL, $value = NULL)
 * @method static ObjectPortal1 latest($column = NULL)
 * @method static ObjectPortal1 oldest($column = NULL)
 * @method static ObjectPortal2 hydrate(array $items)
 * @method static ObjectPortal2 fromQuery($query, $bindings = [])
 * @method static \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Builder[]|\Illuminate\Database\Eloquent\Builder|null find($id, $columns = array (  0 => '*',))
 * @method static ObjectPortal2 findMany($ids, $columns = array (  0 => '*',))
 * @method static \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder[] findOrFail($id, $columns = array (  0 => '*',))
 * @method static \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder findOrNew($id, $columns = array (  0 => '*',))
 * @method static \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder firstOrNew(array $attributes = [], array $values = [])
 * @method static \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder firstOrCreate(array $attributes = [], array $values = [])
 * @method static \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder updateOrCreate(array $attributes, array $values = [])
 * @method static \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder firstOrFail($columns = array (  0 => '*',))
 * @method static \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder|mixed firstOr($columns = array (  0 => '*',), ?Closure $callback = NULL)
 * @method static ObjectPortal3 sole($columns = array (  0 => '*',))
 * @method static mixed value($column)
 * @method static mixed valueOrFail($column)
 * @method static \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Builder[] get($columns = array (  0 => '*',))
 * @method static \Illuminate\Database\Eloquent\Model[]|\Illuminate\Database\Eloquent\Builder[] getModels($columns = array (  0 => '*',))
 * @method static array eagerLoadRelations(array $models)
 * @method static ObjectPortal4 getRelation($name)
 * @method static ObjectPortal5 cursor()
 * @method static ObjectPortal6 pluck($column, $key = NULL)
 * @method static ObjectPortal7 paginate($perPage = NULL, $columns = array (  0 => '*',), $pageName = 'page', $page = NULL)
 * @method static ObjectPortal8 simplePaginate($perPage = NULL, $columns = array (  0 => '*',), $pageName = 'page', $page = NULL)
 * @method static ObjectPortal9 cursorPaginate($perPage = NULL, $columns = array (  0 => '*',), $cursorName = 'cursor', $cursor = NULL)
 * @method static \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder create(array $attributes = [])
 * @method static \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder forceCreate(array $attributes)
 * @method static int update(array $values)
 * @method static int upsert(array $values, $uniqueBy, $update = NULL)
 * @method static int increment($column, $amount = 1, array $extra = [])
 * @method static int decrement($column, $amount = 1, array $extra = [])
 * @method static mixed delete()
 * @method static mixed forceDelete()
 * @method static onDelete(\Closure $callback)
 * @method static bool hasNamedScope($scope)
 * @method static \Illuminate\Database\Eloquent\Builder|mixed scopes($scopes)
 * @method static ObjectPortal1 applyScopes()
 * @method static ObjectPortal1 with($relations, $callback = NULL)
 * @method static ObjectPortal1 without($relations)
 * @method static ObjectPortal1 withOnly($relations)
 * @method static \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder newModelInstance($attributes = [])
 * @method static ObjectPortal1 withCasts($casts)
 * @method static ObjectPortal10 getQuery()
 * @method static ObjectPortal1 setQuery($query)
 * @method static ObjectPortal10 toBase()
 * @method static array getEagerLoads()
 * @method static ObjectPortal1 setEagerLoads(array $eagerLoad)
 * @method static \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Builder getModel()
 * @method static ObjectPortal1 setModel(\Illuminate\Database\Eloquent\Model $model)
 * @method static string qualifyColumn($column)
 * @method static array qualifyColumns($columns)
 * @method static ObjectPortal11 getMacro($name)
 * @method static bool hasMacro($name)
 * @method static ObjectPortal11 getGlobalMacro($name)
 * @method static bool hasGlobalMacro($name)
 * @method static ObjectPortal1 clone()
 * @method static \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder has($relation, $operator = '>=', $count = 1, $boolean = 'and', ?Closure $callback = NULL)
 * @method static \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder orHas($relation, $operator = '>=', $count = 1)
 * @method static \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder doesntHave($relation, $boolean = 'and', ?Closure $callback = NULL)
 * @method static \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder orDoesntHave($relation)
 * @method static \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder whereHas($relation, ?Closure $callback = NULL, $operator = '>=', $count = 1)
 * @method static \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder orWhereHas($relation, ?Closure $callback = NULL, $operator = '>=', $count = 1)
 * @method static \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder whereDoesntHave($relation, ?Closure $callback = NULL)
 * @method static \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder orWhereDoesntHave($relation, ?Closure $callback = NULL)
 * @method static \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder hasMorph($relation, $types, $operator = '>=', $count = 1, $boolean = 'and', ?Closure $callback = NULL)
 * @method static \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder orHasMorph($relation, $types, $operator = '>=', $count = 1)
 * @method static \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder doesntHaveMorph($relation, $types, $boolean = 'and', ?Closure $callback = NULL)
 * @method static \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder orDoesntHaveMorph($relation, $types)
 * @method static \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder whereHasMorph($relation, $types, ?Closure $callback = NULL, $operator = '>=', $count = 1)
 * @method static \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder orWhereHasMorph($relation, $types, ?Closure $callback = NULL, $operator = '>=', $count = 1)
 * @method static \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder whereDoesntHaveMorph($relation, $types, ?Closure $callback = NULL)
 * @method static \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder orWhereDoesntHaveMorph($relation, $types, ?Closure $callback = NULL)
 * @method static \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder whereRelation($relation, $column, $operator = NULL, $value = NULL)
 * @method static \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder orWhereRelation($relation, $column, $operator = NULL, $value = NULL)
 * @method static \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder whereMorphRelation($relation, $types, $column, $operator = NULL, $value = NULL)
 * @method static \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder orWhereMorphRelation($relation, $types, $column, $operator = NULL, $value = NULL)
 * @method static \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder whereMorphedTo($relation, $model, $boolean = 'and')
 * @method static \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder orWhereMorphedTo($relation, $model)
 * @method static ObjectPortal1 whereBelongsTo($related, $relationshipName = NULL, $boolean = 'and')
 * @method static ObjectPortal1 orWhereBelongsTo($related, $relationshipName = NULL)
 * @method static ObjectPortal1 withAggregate($relations, $column, $function = NULL)
 * @method static ObjectPortal1 withCount($relations)
 * @method static ObjectPortal1 withMax($relation, $column)
 * @method static ObjectPortal1 withMin($relation, $column)
 * @method static ObjectPortal1 withSum($relation, $column)
 * @method static ObjectPortal1 withAvg($relation, $column)
 * @method static ObjectPortal1 withExists($relation)
 * @method static \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Builder mergeConstraintsFrom(\Illuminate\Database\Eloquent\Builder $from)
 * @method static bool chunk($count, callable $callback)
 * @method static ObjectPortal6 chunkMap(callable $callback, $count = 1000)
 * @method static bool each(callable $callback, $count = 1000)
 * @method static bool chunkById($count, callable $callback, $column = NULL, $alias = NULL)
 * @method static bool eachById(callable $callback, $count = 1000, $column = NULL, $alias = NULL)
 * @method static ObjectPortal5 lazy($chunkSize = 1000)
 * @method static ObjectPortal5 lazyById($chunkSize = 1000, $column = NULL, $alias = NULL)
 * @method static ObjectPortal5 lazyByIdDesc($chunkSize = 1000, $column = NULL, $alias = NULL)
 * @method static \Illuminate\Database\Eloquent\Model|object|\Illuminate\Database\Eloquent\Builder|null first($columns = array (  0 => '*',))
 * @method static \Illuminate\Database\Eloquent\Model|object|\Illuminate\Database\Eloquent\Builder|null baseSole($columns = array (  0 => '*',))
 * @method static \Illuminate\Database\Eloquent\Builder|mixed tap($callback)
 * @method static \Illuminate\Database\Eloquent\Builder|mixed when($value, $callback, $default = NULL)
 * @method static \Illuminate\Database\Eloquent\Builder|mixed unless($value, $callback, $default = NULL)
 * @method static \Illuminate\Database\Eloquent\Builder ___()
 */
abstract class Model extends EloquentModel
{
    /**
     * Get the table associated with the model.
     *
     * @return string
     */
    public function getTable(): string
    {
        if (isset($this->table)) {
            return $this->table;
        }
        $package = app('package') . '_';
        return $package . strtolower(str_replace('\\', '', class_basename($this)));
    }
}