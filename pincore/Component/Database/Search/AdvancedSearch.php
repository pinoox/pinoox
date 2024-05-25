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


namespace Pinoox\Component\Database\Search;

use Illuminate\Database\Eloquent\Builder;

class AdvancedSearch
{
    protected Builder $query;
    protected $data;
    protected array $rules;
    protected array $replace;
    protected string $logicalOperator = 'AND';
    protected array $relations = [];

    public function __construct(Builder $query, $data, array $rules, array $replace = [], $logicalOperator = 'AND')
    {
        $this->setQuery($query);
        $this->setData($data);
        $this->setRules($rules);
        $this->setReplace($replace);
        $this->setLogicalOperator($logicalOperator);
    }

    public function apply(): void
    {
        $this->query->where(function (Builder $query) {
            foreach ($this->rules as $column => $condition) {
                $this->applyQuery($query, $column, $condition);
            }

            foreach ($this->relations as $relation => $rules) {
                $this->applyRelations($query, $relation, $rules);
            }
        });
    }

    protected function applyQuery(Builder $query, $column, $condition)
    {
        $hasCondition = !is_numeric($column);
        $column = $hasCondition ? $column : $condition;
        $condition = $hasCondition ? $condition : '%like%';

        // relations
        if (str_contains($column, '.')) {
            if ($this->addRelations($query, $column, $condition))
                return;
        }

        $columns = explode(':', $column);
        $data = is_array($this->data) ? ($this->data[$columns[0]] ?? null) : $this->data;
        $column = $columns[1] ?? $columns[0];
        $this->parseCondition($query, $column, $condition, $data);
    }

    protected function hasRelation(Builder $query, $relation): bool
    {
        try {
            $query->getRelation($relation);
            return true;
        } catch (\Exception $exception) {
            return false;
        }
    }

    protected function addRelations(Builder $query, $column, $condition): bool
    {
        $relations = explode('.', $column);
        $column = array_pop($relations);
        $relation = implode('.', $relations);

        if ($this->hasRelation($query, $relation)) {
            $this->relations[$relation][] = [
                'relation' => $relation,
                'column' => $column,
                'condition' => $condition,
            ];

            return true;
        }

        return false;
    }

    protected function applyRelations(Builder $query, $relation, $rules)
    {
        $where = $this->getLogicalOperator() === 'OR' ? 'orWhereHas' : 'whereHas';
        $query->$where($relation, function ($query) use ($rules) {
            $query->where(function ($query) use ($rules) {
                foreach ($rules as $rule) {
                    $column = $rule['column'];
                    $condition = $rule['condition'];

                    $this->applyQuery($query, $column, $condition);
                }
            });
        });
    }

    protected function parseCondition(Builder $query, $column, $condition, $data): void
    {
        $data = $this->replace[$column][$data] ?? $data;

        // functions query
        if ($condition instanceof \Closure) {
            $condition($query, $data, $column);
            return;
        }

        // query operator: OR,And
        $logicalOperator = $this->getLogicalOperator();

        // multiple rule
        $conditions = is_string($condition) ? explode('|', $condition) : $condition;

        foreach ($conditions as $cond) {
            $conditionParts = explode('@', $cond);
            if (count($conditionParts) > 1) {
                $logicalOperator = strtoupper($conditionParts[0]) === 'OR' ? 'OR' : 'AND';
                $cond = $conditionParts[1];
            }

            $condItems = explode(':', $cond);
            $cond = array_shift($condItems);
            $inputs = array_shift($condItems);
            $inputs = explode(',', $inputs);

            $this->applyConditionQuery($query, $data, $column, $cond, $inputs, $logicalOperator);
        }
    }

    protected function applyConditionQuery(Builder $query, $data, $column, $condition, $inputs, $logicalOperator): void
    {
        if ($condition instanceof \Closure) {
            $condition($query, $data, $column);
            return;
        }

        $condition = is_string($condition) ? trim($condition) : $condition;
        switch ($condition) {
            case 'like':
            case '%like':
            case 'like%':
            case '%like%':
                $query->where($column, 'like', $this->getLikePattern($condition, $data), $logicalOperator);
                break;
            case 'not like':
            case '%not like':
            case 'not like%':
            case '%not like%':
                $query->where($column, 'not like', $this->getLikePattern($condition, $data, 'not like'), $logicalOperator);
                break;
            case '=':
            case '>':
            case '<':
            case '>=':
            case '<=':
            case '<>':
                $query->where($column, $condition, $data, $logicalOperator);
                break;
            case 'in':
                $query->whereIn($column, $data, $logicalOperator);
                break;
            case 'not in':
                $query->whereNotIn($column, $data, $logicalOperator);
                break;
            case 'not':
                $query->whereNot($column, $data, $logicalOperator);
                break;
            case 'not null':
                $query->whereNotNull($column, $logicalOperator);
                break;
            case 'null':
                $query->whereNull($column, $logicalOperator);
                break;
            case 'between':
                if (is_array($inputs) && count($inputs) === 2) {
                    $data = is_string($data) ? [$data] : $data;
                    $query->whereRaw('? BETWEEN ' . $inputs[0] . ' AND ' . $inputs[1], $data, $logicalOperator);
                } else if (empty($inputs) && is_array($data) && count($data) === 2) {
                    $query->whereBetween($column, [$data[0], $data[1]], $logicalOperator);
                }
                break;
            case 'not between':
                if (is_array($inputs) && count($inputs) === 2) {
                    $query->whereRaw('? NOT BETWEEN ' . $inputs[0] . ' AND ' . $inputs[1], [$data], $logicalOperator);
                } else if (empty($inputs) && is_array($data) && count($data) === 2) {
                    $query->whereNotBetween($column, [$data[0], $data[1]], $logicalOperator);
                }
                break;
            case 'between columns':
                if (is_array($inputs) && count($inputs) === 2) {
                    $query->whereBetweenColumns($column, [$inputs[0], $inputs[1]], $logicalOperator);
                }
                break;
            case 'not between columns':
                if (is_array($inputs) && count($inputs) === 2) {
                    $query->whereNotBetweenColumns($column, [$inputs[0], $inputs[1]], $logicalOperator);
                }
                break;
            case 'concat':
                $data = is_string($data) ? $data : '';
                $query->whereRaw("CONCAT(" . implode('," ",', $inputs) . ") LIKE ?", '%' . $data . '%', $logicalOperator);
                break;
            case 'method':
                $methodName = array_shift($inputs);
                $query->$methodName($this->data[$column], ...$inputs);
                break;
            default:
                $condition = $this->replaceCondition($condition, $data);
                $query->whereRaw($condition, $data, $logicalOperator);
                break;
        }
    }

    protected function replaceCondition($condition, $data)
    {
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $normalize = $this->normalizeDateFoReplaceCondition($value);
                $condition = str_replace('{' . $key . '}', $normalize, $condition);
            }
        }

        return $condition;
    }

    protected function normalizeDateFoReplaceCondition($data)
    {
        if (is_array($data))
            return '(' . implode(',', $data) . ')';

        return $data;
    }

    protected function getLikePattern($condition, $value, $type = 'like'): string
    {
        return match ($condition) {
            '%' . $type => '%' . $value,
            $type . '%' => $value . '%',
            '%' . $type . '%' => '%' . $value . '%',
            default => $value,
        };
    }

    /**
     * @return string
     */
    public function getLogicalOperator(): string
    {
        return $this->logicalOperator;
    }

    /**
     * @param string $logicalOperator
     */
    public function setLogicalOperator(string $logicalOperator): void
    {
        $this->logicalOperator = strtoupper($logicalOperator);
    }

    /**
     * @return array
     */
    public function getReplace(): array
    {
        return $this->replace;
    }

    /**
     * @param array $replace
     */
    public function setReplace(array $replace): void
    {
        $this->replace = $replace;
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     */
    public function setData($data): void
    {
        $this->data = $data;
    }

    /**
     * @return array
     */
    public function getRules(): array
    {
        return $this->rules;
    }

    /**
     * @param array $rules
     */
    public function setRules(array $rules): void
    {
        $this->rules = $rules;
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
}