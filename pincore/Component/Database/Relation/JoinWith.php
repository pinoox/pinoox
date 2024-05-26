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
use Pinoox\Portal\App\App;

trait JoinWith
{
    protected function scopeJoinWith(Builder $query, string|array $relations, $type = 'inner', $where = false):
    Builder
    {
        $relationJoiner = new RelationJoiner($query, [
            'pincore', App::package()
        ]);

        return $relationJoiner->joinWith($relations, $type, $where);
    }
}