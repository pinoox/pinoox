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


namespace Pinoox\Model\Scope;


use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class AppScope implements Scope
{
    /** @param Closure(): list<string> $resolver */
    public function __construct(private readonly Closure $resolver)
    {
    }

    /**
     * @param Closure(): list<string> $resolver
     */
    public static function for(Closure $resolver): self
    {
        return new self($resolver);
    }

    public function apply(Builder $builder, Model $model)
    {
        $apps = ($this->resolver)();

        if ($apps === []) {
            return $builder;
        }

        if (count($apps) === 1) {
            return $builder->where('app', $apps[0]);
        }

        return $builder->whereIn('app', $apps);
    }
}
