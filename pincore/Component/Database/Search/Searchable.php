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

/**
 * @method static Builder advancedSearch($data, ?array $rules = null, ?array $replaces = null, ?string $boolean = null)
 * @method static Builder orAdvancedSearch($data, ?array $rules = null, ?array $replaces = null)
 * @method static Builder andAdvancedSearch($data, ?array $rules = null, ?array $replaces = null)
 */
trait Searchable
{
    /**
     * Define searchable rules
     *
     * @var array
     */
    protected array $searchableRules = [];

    /**
     * replace for searchable data
     *
     * @var array
     */
    protected array $searchableReplaces = [];

    /**
     * logical operator searchable
     *
     * @var string
     */
    protected string $logicalOperator = 'AND';

    /**
     * Scope: search
     *
     * @param Builder $query
     * @param $data
     * @param array|null $rules
     * @param array|null $replaces
     * @param string|null $boolean
     * @return Builder
     */
    public function scopeAdvancedSearch(Builder $query, $data, ?array $rules = null, ?array $replaces = null, ?string $boolean = null): Builder
    {
        if (empty($data)) {
            return $query;
        }

        $rules = $rules ?? $this->getSearchableRules();
        $replaces = $replaces ?? $this->getSearchableReplaces();
        $boolean = $boolean ?? $this->getLogicalOperator();

        // Apply AdvancedSearch logic
        $advancedSearch = new AdvancedSearch($query, $data, $rules, $replaces, $boolean);
        $advancedSearch->apply();

        return $query;
    }

    public function scopeOrAdvancedSearch(Builder $query, $data, ?array $rules = null, ?array $replaces = null): Builder
    {
        return $this->scopeAdvancedSearch($query, $data, $rules, $replaces, 'OR');
    }

    public function scopeAndAdvancedSearch(Builder $query, $data, ?array $rules = null, ?array $replaces = null): Builder
    {
        return $this->scopeAdvancedSearch($query, $data, $rules, $replaces, 'AND');
    }

    /**
     * @return array
     */
    public function getSearchableRules(): array
    {
        return $this->searchableRules;
    }

    /**
     * @param array $searchableRules
     */
    public function setSearchableRules(array $searchableRules): void
    {
        $this->searchableRules = $searchableRules;
    }

    /**
     * @return array
     */
    public function getSearchableReplaces(): array
    {
        return $this->searchableReplaces;
    }

    /**
     * @param array $searchableReplaces
     */
    public function setSearchableReplaces(array $searchableReplaces): void
    {
        $this->searchableReplaces = $searchableReplaces;
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
        $this->logicalOperator = $logicalOperator;
    }
}
