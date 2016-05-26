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

namespace Spiritix\LadaCache;

use Spiritix\LadaCache\Reflector\ReflectorInterface;

/**
 * Tagger creates a list of tags for a query using a reflector.
 *
 * @package Spiritix\LadaCache
 * @author  Loo Say Phoon <sploo@alphline.com>
 */
class FunctionTagger
{
    /**
     * Database tag prefix.
     */
    const PREFIX_DATABASE = 'tags:database:';

    /**
     * Table tag prefix.
     */
    const PREFIX_TABLE = ':table:';

    /**
     * Row tag prefix.
     */
    const PREFIX_ROW = ':row:';

    /**
     * Reflector instance.
     *
     * @var ReflectorInterface
     */
    private $reflector;

    /**
     * Defines if tables should be considered as tags.
     *
     * @var bool
     */
    private $considerTables = true;

    /**
     * Defines if rows should be considered as tags.
     *
     * @var bool
     */
    private $considerRows = true;

    /**
     * Initialize tagger.
     *
     * @param ReflectorInterface $reflector      Reflector instance
     * @param bool               $considerTables If tables should be considered as tags
     */
    public function __construct(ReflectorInterface $reflector, $considerTables = true)
    {
        $this->reflector = $reflector;
        $this->considerTables = $considerTables;

        $this->considerRows = (bool) config('lada-cache.consider-rows');
    }

    /**
     * Compiles and returns the tags.
     *
     * @return array
     */
    public function getTags()
    {
        $tags = [];
        $database = $this->prefix($this->reflector->getDatabase(), self::PREFIX_DATABASE);
        $rows = $this->reflector->getRows();

        // Check if affected rows are available or if granularity is set to not consider rows
        // In this case just use the previously prepared tables as tags
        $rows = $this->reflector->getRows();
        $tables = $this->reflector->getTables();

        if (empty($rows) || $this->considerRows === false) {
            $prefix_tables = $this->prefix($tables, self::PREFIX_TABLE);
            return $this->prefix($prefix_tables, $database);
        }

        // Now loop trough tables and create a tag for each row
        foreach ($tables as $table) {
            $prefix_table = $this->prefix($table, self::PREFIX_TABLE);            
            if(array_key_exists($table, $rows)) {
                if(is_null($rows[$table])){
                    $tags = array_merge($tags, $prefix_table);
                }else{
                    if(is_array($rows[$table])){
                        $tags = array_merge($tags, $this->prefix($rows[$table], $this->prefix(self::PREFIX_ROW,$prefix_table)));
                    }else{
                        $tags = array_merge($tags, $this->prefix([$rows[$table]], $this->prefix(self::PREFIX_ROW,$prefix_table)));
                    }

                    // Add tables to tags if requested
                    if ($this->considerTables) {
                        $tags = array_merge($prefix_table, $tags);
                    }
                }
            }else{
                $tags = array_merge($tags, [$prefix_table]);
            }
        }

        return $this->prefix($tags, $database);
    }

    /**
     * Prepends a prefix to one or multiple values.
     *
     * @param string|array $value  Either a string or an array of strings.
     * @param string       $prefix The prefix to be prepended.
     *
     * @return string|array
     */
    protected function prefix($value, $prefix)
    {
        if (is_array($value)) {
            return array_map(function($item) use($prefix) {
                return $this->prefix($item, $prefix);
            }, $value);
        }

        return $prefix . $value;
    }
}
