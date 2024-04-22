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


namespace Pinoox\Component\Database\Model;

use Illuminate\Database\Eloquent\Builder;

trait SearchableTrait
{
    /**
     * Define searchable columns
     *
     * @var array
     */
    protected array $searchableColumns = [];

    /**
     * Ignore columns not in fillable array
     *
     * @var bool
     */
    protected bool $ignoreNonFillable = false;

    /**
     * Set searchable columns
     *
     * @param array $columns
     * @return $this
     */
    public function setSearchableColumns(array $columns): static
    {
        $this->searchableColumns = $this->normalizeSearchableColumns($columns);
        return $this;
    }

    /**
     * Normalize searchable columns
     *
     * @param array $columns
     * @return array
     */
    protected function normalizeSearchableColumns(array $columns): array
    {
        $normalizedColumns = [];

        foreach ($columns as $column => $condition) {
            if (is_array($condition)) {
                if (isset($condition[0])) { // Check if it's an alias
                    $normalizedColumns[$column] = [
                        'type' => 'alias',
                        'value' => $condition,
                    ];
                } else { // It's a concatenated column
                    $normalizedColumns[$column] = [
                        'type' => 'concat',
                        'value' => $condition,
                    ];
                }
            } elseif (is_string($condition)) { // Check if it's a custom method
                $normalizedColumns[$column] = [
                    'type' => 'method',
                    'value' => $condition,
                ];
            } else {
                $normalizedColumns[$column] = [
                    'type' => 'column',
                    'value' => $condition,
                ];
            }
        }

        return $normalizedColumns;
    }

    /**
     * Set ignore non fillable columns
     *
     * @param bool $ignore
     * @return $this
     */
    public function setIgnoreNonFillable($ignore): static
    {
        $this->ignoreNonFillable = $ignore;
        return $this;
    }

    /**
     * Scope: search
     *
     * @param Builder $query
     * @param string $keyword
     * @return Builder
     */
    public function scopeSearch(Builder $query, string $keyword = ''): Builder
    {
        if (!empty($keyword)) {
            $query->where(function ($q) use ($keyword) {
                foreach ($this->searchableColumns as $column => $config) {
                    if (!$this->ignoreNonFillable || in_array($column, $this->fillable)) {
                        switch ($config['type']) {
                            case 'alias':
                                $q->orWhere($column, $config['value'][$keyword]);
                                break;
                            case 'concat':
                                $q->orWhereRaw("CONCAT(" . implode(', ', $config['value']) . ") LIKE '%$keyword%'");
                                break;
                            case 'method':
                                $method = $config['value'];
                                $q->$method($keyword);
                                break;
                            case 'column':
                                $q->orWhere($column, 'like', "%{$keyword}%");
                                break;
                        }
                    }
                }
            });
        }

        return $query;
    }
}
