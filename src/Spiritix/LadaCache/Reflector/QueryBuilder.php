<?php
/**
 * This file is part of the spiritix/lada-cache package.
 *
 * @copyright Copyright (c) Matthias Isler <mi@matthias-isler.ch>
 * @license   MIT
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Spiritix\LadaCache\Reflector;

use Spiritix\LadaCache\Database\QueryBuilder as EloquentQueryBuilder;

/**
 * Query builder reflector provides information about an Eloquent query builder object.
 *
 * @package Spiritix\LadaCache\Reflector
 * @author  Matthias Isler <mi@matthias-isler.ch>
 */
class QueryBuilder implements HashableReflectorInterface
{
    use ReflectorTrait;
    /**
     * Since the query builder doesn't know about the related model, we have no way to figure out the name of the
     * primary key column. If someone is not using this value as primary key column it won't break anything, it just
     * wont consider the row ID's when creating the cache tags.
     *
     * @todo Get the primary key column from the model.
     */
    const PRIMARY_KEY_COLUMN = 'id';

    /**
     * Query builder instance.
     *
     * @var QueryBuilder
     */
    protected $queryBuilder;    

    /**
     * Initialize reflector.
     *
     * @param EloquentQueryBuilder $queryBuilder
     */
    public function __construct(EloquentQueryBuilder $queryBuilder)
    {        
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getDatabase()
    {
        return $this->queryBuilder
            ->getConnection()
            ->getDatabaseName(); // Fuck this shit
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getTables()
    {
        // Get main table
        $tables =  $this->resolveTable($this->queryBuilder->from);
        
        // Add possible join tables
        $joins = $this->queryBuilder->joins ?: [];
        foreach ($joins as $join) {            
            $tables =  array_merge($tables, $this->resolveTable($join->table));            
        }

        return array_unique($tables);
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getRows()
    {
        $rows = [];

        $wheres = $this->queryBuilder->wheres ?: [];
        foreach ($wheres as $where) {

            // Skip unsupported clauses
            if (!isset($where['column'])) {
                continue;
            }

            list($table, $column) = $this->splitTableAndColumn($where['column']);

            // Make sure that the where clause applies for the main table
            if ($table !== null && $table != $this->queryBuilder->from) {
                continue;
            }

            // Make sure that the where clause applies for the primary key column
            if ($column != self::PRIMARY_KEY_COLUMN) {
                continue;
            }

            if ($where['type'] == 'Basic') {

                if ($where['operator'] == '=' && is_int($where['value'])) {
                    $rows[] = $where['value'];
                }
            }

            //We need check whether the where value is null, otherwise will have Error: Unsupported operand types
            if ($where['type'] == 'In' && $where['values']) {
                $rows += $where['values'];
            }
        }

        return $rows;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getSql()
    {
        return $this->queryBuilder->toSql();
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->queryBuilder->getBindings();
    }

    /**
     * Splits an SQL column identifier into table and column.
     *
     * @param string $sqlString SQL column identifier
     *
     * @return array [table|null, column]
     */
    protected function splitTableAndColumn($sqlString)
    {
        // Most column identifiers don't contain a database or table name
        // In this case just return what we've got
        if (strpos($sqlString, '.') === false) {
            return [null, $sqlString];
        }

        $parts = explode('.', $sqlString);

        // If we have three parts, the identifier also contains the database name
        if (count($parts) === 3) {
            $table = $parts[1];
        }
        // Otherwise it contains table and column
        else {
            $table = $parts[0];
        }

        // Column is always the last part
        $column = end($parts);

        return [$table, $column];
    }
}