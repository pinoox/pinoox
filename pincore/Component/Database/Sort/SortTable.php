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

class SortTable
{
    protected Builder $query;
    protected array $supports;
    protected ?string $field;
    protected ?string $direction;
    protected array $allowedColumns;
    protected bool $enableAllowedColumns = false;

    public function __construct(Builder $query, ?string $field, ?string $direction = null, array $supports = [], ?array $allowedColumns = null)
    {
        $this->query = $query;
        $this->field = $field;
        $this->direction = $direction ?? 'asc';
        $this->supports = $supports;
        $this->allowedColumns = $allowedColumns ?? [];
        $this->enableAllowedColumns = !empty($allowedColumns);
    }

    public function apply(): void
    {
        if ($this->field && $this->direction !== 'none') {
            $this->orderBy($this->field, $this->direction);
        }
    }

    protected function orderBy(string $column, string $direction): void
    {
        $direction = strtolower($direction) === 'asc' ? 'asc' : 'desc';

        if (isset($this->supports[$column])) {
            $this->applyBySupport($this->supports[$column]);
        } elseif (in_array($column, $this->supports)) {
            $this->query->orderBy($column, $direction);
        } elseif (!$this->enableAllowedColumns || in_array($column, $this->allowedColumns)) {
            $this->query->orderBy($column, $direction);
        }
    }

    protected function applyBySupport($condition): void
    {
        if ($condition instanceof \Closure) {
            $condition($this->query, $this->field, $this->direction);
            return;
        }

        if (is_array($condition) && !empty($condition)) {
            if (method_exists($this->query, $condition[0])) {
                $methodName = array_shift($condition);
                $this->query->$methodName(...$condition);
            } else {
                $raw = array_shift($condition);
                $this->query->orderByRaw($raw . ' ' . $this->direction, ...$condition);
            }
            return;
        }

        $parts = explode(':', $condition);
        if (count($parts) >= 2) {
            $type = trim($parts[0]);
            $condition = trim(implode(',',$parts));
        } else {
            $condition = trim($condition);
            $type = $condition;
        }


        switch ($type) {
            case 'concat':
                $inputs = explode(',', $condition);
                $this->query->orderByRaw("CONCAT(" . implode('," ",', $inputs) . ") " . $this->direction);
                break;
            case 'method':
                $inputs = explode(',', $condition);
                $methodName = array_shift($inputs);
                $this->query->$methodName(...$inputs);
                break;
            default:
                $this->query->orderByRaw($condition . ' ' . $this->direction);
                break;
        }
    }


    /**
     * @return Builder
     */
    public function getQuery(): Builder
    {
        return $this->query;
    }

    /**
     * @param Builder $query
     */
    public function setQuery(Builder $query): void
    {
        $this->query = $query;
    }

    /**
     * @return array
     */
    public function getSupports(): array
    {
        return $this->supports;
    }

    /**
     * @param array $supports
     */
    public function setSupports(array $supports): void
    {
        $this->supports = $supports;
    }

    /**
     * @return ?string
     */
    public function getField(): ?string
    {
        return $this->field;
    }

    /**
     * @param ?string $field
     */
    public function setField(?string $field): void
    {
        $this->field = $field;
    }

    /**
     * @return ?string
     */
    public function getDirection(): ?string
    {
        return $this->direction;
    }

    /**
     * @param ?string $direction
     */
    public function setDirection(?string $direction): void
    {
        $this->direction = $direction;
    }

    /**
     * @return array
     */
    public function getAllowedColumns(): array
    {
        return $this->allowedColumns;
    }

    /**
     * @param array $allowedColumns
     */
    public function setAllowedColumns(array $allowedColumns): void
    {
        $this->allowedColumns = $allowedColumns;
    }

    /**
     * @return bool
     */
    public function isEnableAllowedColumns(): bool
    {
        return $this->enableAllowedColumns;
    }

    /**
     * @param bool $enableAllowedColumns
     */
    public function setEnableAllowedColumns(bool $enableAllowedColumns): void
    {
        $this->enableAllowedColumns = $enableAllowedColumns;
    }
}