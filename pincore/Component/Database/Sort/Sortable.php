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


namespace Pinoox\Component\Database\Sort;

use Illuminate\Database\Eloquent\Builder;

/**
 * @method static Builder sortFiled($field, $direction = 'asc')
 */
trait Sortable
{
    protected array $sortableSupports = [];

    protected bool $allowedAnySortable = false;

    public function scopeSortFiled(Builder $query, $field, $direction = 'asc'): Builder
    {
        $sortTable = new SortTable($query, $field, $direction, $this->getSortableSupports(), $this->fillable);
        $sortTable->setEnableAllowedColumns(!$this->isAllowedAnySortable());
        $sortTable->apply();
        return $query;
    }

    /**
     * @return bool
     */
    protected function isAllowedAnySortable(): bool
    {
        return $this->allowedAnySortable;
    }

    /**
     * @param bool $allowedAnySortable
     */
    protected function setAllowedAnySortable(bool $allowedAnySortable): void
    {
        $this->allowedAnySortable = $allowedAnySortable;
    }

    /**
     * @return array
     */
    public function getSortableSupports(): array
    {
        return $this->sortableSupports;
    }

    /**
     * @param array $sortableSupports
     */
    public function setSortableSupports(array $sortableSupports): void
    {
        $this->sortableSupports = $sortableSupports;
    }
}