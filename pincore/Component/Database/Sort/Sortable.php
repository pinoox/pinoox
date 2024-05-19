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
 * @method static Builder sortFiled($field, $direction = 'asc', ?array $support = null)
 */
trait Sortable
{
    protected array $sortableSupports = [];

    protected bool $allowedAnySortable = false;

    public function scopeSortFiled(Builder $query, $field, $direction = 'asc', ?array $support = null): Builder
    {
        $support = is_null($support) ? $this->getSortableSupports() : $support;


        $sortTable = new SortTable($query, $field, $direction, $support, $this->getAllowedColumns());
        $sortTable->setEnableAllowedColumns(!$this->isAllowedAnySortable());
        $sortTable->apply();
        return $query;
    }

    private function getAllowedColumns(): array
    {
        return array_merge($this->fillable, (array)$this->primaryKey);
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