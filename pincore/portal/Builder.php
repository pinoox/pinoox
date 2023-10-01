<?php

/**
 * ***  *  *     *  ****  ****  *    *
 *   *  *  * *   *  *  *  *  *   *  *
 * ***  *  *  *  *  *  *  *  *    *
 *      *  *   * *  *  *  *  *   *  *
 *      *  *    **  ****  ****  *    *
 *
 * @author   Pinoox
 * @link https://www.pinoox.com
 * @license  https://opensource.org/licenses/MIT MIT License
 */

namespace pinoox\portal;

use pinoox\component\source\Portal;

/**
 * @method static make(array $attributes = [])
 * @method static withGlobalScope($identifier, $scope)
 * @method static withoutGlobalScope($scope)
 * @method static withoutGlobalScopes(?array $scopes = NULL)
 * @method static removedScopes()
 * @method static whereKey($id)
 * @method static whereKeyNot($id)
 * @method static where($column, $operator = NULL, $value = NULL, $boolean = 'and')
 * @method static firstWhere($column, $operator = NULL, $value = NULL, $boolean = 'and')
 * @method static orWhere($column, $operator = NULL, $value = NULL)
 * @method static latest($column = NULL)
 * @method static oldest($column = NULL)
 * @method static hydrate(array $items)
 * @method static fromQuery($query, $bindings = [])
 * @method static find($id, $columns = array (  0 => '*',))
 * @method static findMany($ids, $columns = array (  0 => '*',))
 * @method static findOrFail($id, $columns = array (  0 => '*',))
 * @method static findOrNew($id, $columns = array (  0 => '*',))
 * @method static firstOrNew(array $attributes = [], array $values = [])
 * @method static firstOrCreate(array $attributes = [], array $values = [])
 * @method static updateOrCreate(array $attributes, array $values = [])
 * @method static firstOrFail($columns = array (  0 => '*',))
 * @method static firstOr($columns = array (  0 => '*',), ?Closure $callback = NULL)
 * @method static sole($columns = array (  0 => '*',))
 * @method static value($column)
 * @method static valueOrFail($column)
 * @method static get($columns = array (  0 => '*',))
 * @method static getModels($columns = array (  0 => '*',))
 * @method static eagerLoadRelations(array $models)
 * @method static getRelation($name)
 * @method static cursor()
 * @method static pluck($column, $key = NULL)
 * @method static paginate($perPage = NULL, $columns = array (  0 => '*',), $pageName = 'page', $page = NULL)
 * @method static simplePaginate($perPage = NULL, $columns = array (  0 => '*',), $pageName = 'page', $page = NULL)
 * @method static cursorPaginate($perPage = NULL, $columns = array (  0 => '*',), $cursorName = 'cursor', $cursor = NULL)
 * @method static create(array $attributes = [])
 * @method static forceCreate(array $attributes)
 * @method static update(array $values)
 * @method static upsert(array $values, $uniqueBy, $update = NULL)
 * @method static increment($column, $amount = 1, array $extra = [])
 * @method static decrement($column, $amount = 1, array $extra = [])
 * @method static delete()
 * @method static forceDelete()
 * @method static onDelete(\Closure $callback)
 * @method static hasNamedScope($scope)
 * @method static scopes($scopes)
 * @method static applyScopes()
 * @method static with($relations, $callback = NULL)
 * @method static without($relations)
 * @method static withOnly($relations)
 * @method static newModelInstance($attributes = [])
 * @method static withCasts($casts)
 * @method static getQuery()
 * @method static setQuery($query)
 * @method static toBase()
 * @method static getEagerLoads()
 * @method static setEagerLoads(array $eagerLoad)
 * @method static getModel()
 * @method static setModel(\Illuminate\Database\Eloquent\Model $model)
 * @method static qualifyColumn($column)
 * @method static qualifyColumns($columns)
 * @method static getMacro($name)
 * @method static hasMacro($name)
 * @method static getGlobalMacro($name)
 * @method static hasGlobalMacro($name)
 * @method static clone()
 * @method static has($relation, $operator = '>=', $count = 1, $boolean = 'and', ?Closure $callback = NULL)
 * @method static orHas($relation, $operator = '>=', $count = 1)
 * @method static doesntHave($relation, $boolean = 'and', ?Closure $callback = NULL)
 * @method static orDoesntHave($relation)
 * @method static whereHas($relation, ?Closure $callback = NULL, $operator = '>=', $count = 1)
 * @method static orWhereHas($relation, ?Closure $callback = NULL, $operator = '>=', $count = 1)
 * @method static whereDoesntHave($relation, ?Closure $callback = NULL)
 * @method static orWhereDoesntHave($relation, ?Closure $callback = NULL)
 * @method static hasMorph($relation, $types, $operator = '>=', $count = 1, $boolean = 'and', ?Closure $callback = NULL)
 * @method static orHasMorph($relation, $types, $operator = '>=', $count = 1)
 * @method static doesntHaveMorph($relation, $types, $boolean = 'and', ?Closure $callback = NULL)
 * @method static orDoesntHaveMorph($relation, $types)
 * @method static whereHasMorph($relation, $types, ?Closure $callback = NULL, $operator = '>=', $count = 1)
 * @method static orWhereHasMorph($relation, $types, ?Closure $callback = NULL, $operator = '>=', $count = 1)
 * @method static whereDoesntHaveMorph($relation, $types, ?Closure $callback = NULL)
 * @method static orWhereDoesntHaveMorph($relation, $types, ?Closure $callback = NULL)
 * @method static whereRelation($relation, $column, $operator = NULL, $value = NULL)
 * @method static orWhereRelation($relation, $column, $operator = NULL, $value = NULL)
 * @method static whereMorphRelation($relation, $types, $column, $operator = NULL, $value = NULL)
 * @method static orWhereMorphRelation($relation, $types, $column, $operator = NULL, $value = NULL)
 * @method static whereMorphedTo($relation, $model, $boolean = 'and')
 * @method static orWhereMorphedTo($relation, $model)
 * @method static whereBelongsTo($related, $relationshipName = NULL, $boolean = 'and')
 * @method static orWhereBelongsTo($related, $relationshipName = NULL)
 * @method static withAggregate($relations, $column, $function = NULL)
 * @method static withCount($relations)
 * @method static withMax($relation, $column)
 * @method static withMin($relation, $column)
 * @method static withSum($relation, $column)
 * @method static withAvg($relation, $column)
 * @method static withExists($relation)
 * @method static mergeConstraintsFrom(\Illuminate\Database\Eloquent\Builder $from)
 * @method static chunk($count, callable $callback)
 * @method static chunkMap(callable $callback, $count = 1000)
 * @method static each(callable $callback, $count = 1000)
 * @method static chunkById($count, callable $callback, $column = NULL, $alias = NULL)
 * @method static eachById(callable $callback, $count = 1000, $column = NULL, $alias = NULL)
 * @method static lazy($chunkSize = 1000)
 * @method static lazyById($chunkSize = 1000, $column = NULL, $alias = NULL)
 * @method static lazyByIdDesc($chunkSize = 1000, $column = NULL, $alias = NULL)
 * @method static first($columns = array (  0 => '*',))
 * @method static baseSole($columns = array (  0 => '*',))
 * @method static tap($callback)
 * @method static when($value, $callback, $default = NULL)
 * @method static unless($value, $callback, $default = NULL)
 * @method static \Illuminate\Database\Eloquent\Builder ___()
 *
 * @see \Illuminate\Database\Eloquent\Builder
 */
class Builder extends Portal
{
	public static function __register(): void
	{
		self::__bind(\Illuminate\Database\Eloquent\Builder::class);
	}


	/**
	 * Get the registered name of the component.
	 * @return string
	 */
	public static function __name(): string
	{
		return 'builder';
	}


	/**
	 * Get exclude method names .
	 * @return string[]
	 */
	public static function __exclude(): array
	{
		return [];
	}


	/**
	 * Get method names for callback object.
	 * @return string[]
	 */
	public static function __callback(): array
	{
		return [];
	}
}
