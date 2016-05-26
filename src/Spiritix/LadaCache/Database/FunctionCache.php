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

namespace Spiritix\LadaCache\Database;

use Log;

use Spiritix\LadaCache\Hasher;
use Spiritix\LadaCache\Manager;
use Spiritix\LadaCache\FunctionTagger as Tagger;
use Spiritix\LadaCache\Reflector\FunctionCache as FunctionReflector;


/**
 * Function to cache functions within the model
 *
 * @package Spiritix\LadaCache\Database
 * @author  Loo Say Phoon <sploo@herdhr.com>
 */
class FunctionCache
{
    private $database;
    private $class;
    private $function;
    private $arguments;

     /**
     * Initialize reflector.
     *
     * @param string database
     */
    public function __construct(string $database, string $class, string $function, array $arguments)
    {
        $this->database = $database;
        $this->class = $class;
        $this->function = $function;
        $this->arguments = self::cloneArray($arguments);
    }


    /**
     * Execute and cache function results.
     *
     * @param string $database
     * @param string $class
     * @param string $function
     * @param array $arguments
     * @param array $tables
     * @param array $rows
     *
     * @return results
     */
    public static function execute(string $database, string $class, string $function, array $arguments, array $tables, array $rows) {
        $arguments_key = self::cloneArray($arguments);
        $reflector = new FunctionReflector ($database, $class, $function, $arguments_key, $tables, $rows);
        $manager = new Manager($reflector);

        // Check if function should be cached
        if(!$manager->shouldCache()) {
            return call_user_func_array(array($class, $function), $arguments);
        }

        // Resolve the actual cache
         $cache = app()->make('lada.cache');

        $hasher = new Hasher($reflector);
        $tagger = new Tagger($reflector, false);

        // Check if a cached version is available
        $key = $hasher->getHash();
        if ($cache->has($key)) {
            return $cache->get($key);
        }


        // If not execute query and add to cache
        $result = call_user_func_array(array($class, $function), $arguments);
        $cache->set($key, $tagger->getTags(), $result);
        return $result;
    }

    /**
     * Execute and cache static function results.
     *
     * @param string $database
     * @param string $class
     * @param string $function
     * @param array $arguments
     * @param array $tables
     * @param array $rows
     *
     * @return results
     */
    public static function executeStatic(string $database, string $class, string $function, array $arguments, array $tables, array $rows) {
        $arguments_key =self::cloneArray($arguments);

        $reflector = new FunctionReflector ($database, $class, $function, $arguments_key, $tables, $rows);
        $manager = new Manager($reflector);

        // Check if function should be cached
        if(!$manager->shouldCache()) {
            return forward_static_call_array(array($class, $function), $arguments);
        }

        // Resolve the actual cache
        $cache = app()->make('lada.cache');
        $hasher = new Hasher($reflector);
        $tagger = new Tagger($reflector, false);

        // Check if a cached version is available
        $key = $hasher->getHash();
        if ($cache->has($key)) {
            return $cache->get($key);
        }

        // If not execute query and add to cache
        $result = forward_static_call_array(array($class, $function), $arguments);
        $cache->set($key, $tagger->getTags(), $result);
        return $result;
    }

    /**
     * Check if we should use caching
     *
     *
     * @return boolean
     */
    public function shouldCache(){
        $reflector = $this->getKeyFunctionReflector ();
        $manager = new Manager ($reflector);
        return $manager->shouldCache();
    }

    /**
     * Check if the cache is available
     *
     *
     * @param string $key
     *
     * @return boolean
     */
    public function has(string $key) {
        $has = false;
        if($this->shouldCache()){
            $reflector = $this->getKeyFunctionReflector ($key);
            $manager = new Manager ($reflector);

            // Resolve the actual cache
            $cache = app()->make('lada.cache');
            $hasher = new Hasher($reflector);

            $key = $hasher->getHash();
            $has = $cache->has($key);
        }
        return $has;
    }

    /**
     * set cache value
     *
     *
     * @param string $key
     * @param object $key
     * @param array $tables
     * @param array $rows
     *
     * @return boolean
     */
    public function set(string $key, $value, array $tables, array $rows) {
        if($this->shouldCache()){
            $reflector = $this->getKeyFunctionReflector ($key, $tables, $rows);
            $manager = new Manager($reflector);

            $cache = app()->make('lada.cache');
            $hasher = new Hasher($reflector);
            $tagger = new Tagger($reflector, false);

            $key = $hasher->getHash();
            $cache->set($key, $tagger->getTags(), $value);
        }
    }

    /**
     * Get cache value
     *
     * @param string $key
     *
     * @return results
     */
    public function get($key) {
        if($this->shouldCache()){
            $reflector = $this->getKeyFunctionReflector ($key);
            $manager = new Manager($reflector);

            $cache = app()->make('lada.cache');
            $hasher = new Hasher($reflector);

            $key = $hasher->getHash();
            return $cache->get($key);
        }
    }

    private function getKeyFunctionReflector(string $key = "", array $tables = [], array $rows = []) {
        return new FunctionReflector ($this->database, $this->class, $this->function . "\\$key", $this->arguments, $tables, $rows);
    }

    private static function cloneArray(array $objects){
        $clone = [];
        foreach ($objects as $k => $v){
            if(is_object($v)){
                $clone[$k] = clone $v;
            }else{
                $clone[$k] = $v;
            }
        }
        return $clone;
    }
}
