<?php

namespace pinoox\component\querybuilder\queries;

use pinoox\component\querybuilder\{Query, Utilities};

/**
 * Class Json
 *
 * @package pinoox\component\querybuilder\queries
 */
class Json extends Common
{

    /** @var mixed */
    protected $fromTable;
    /** @var mixed */
    protected $fromAlias;
    /** @var boolean */
    protected $convertTypes = false;

    /**
     * Json constructor
     *
     * @param Query  $mvcquerybuilder
     * @param string $table
     */
    public function __construct(Query $mvcquerybuilder, string $table)
    {
        $clauses = [
            'SELECT'   => ', ',
            'JOIN'     => [$this, 'getClauseJoin'],
            'WHERE'    => [$this, 'getClauseWhere'],
            'GROUP BY' => ',',
            'HAVING'   => ' AND ',
            'ORDER BY' => ', ',
            'LIMIT'    => null,
            'OFFSET'   => null,
            "\n--"     => "\n--",
        ];

        parent::__construct($mvcquerybuilder, $clauses);

        // initialize statements
        $tableParts = explode(' ', $table);
        $this->fromTable = reset($tableParts);
        $this->fromAlias = end($tableParts);

        $this->statements['SELECT'][] = '';
        $this->joins[] = $this->fromAlias;

        if (isset($mvcquerybuilder->convertTypes) && $mvcquerybuilder->convertTypes) {
            $this->convertTypes = true;
        }
    }
}
