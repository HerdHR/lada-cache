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

/**
 * Model reflector provides information about an Eloquent model object.
 *
 * @package Spiritix\LadaCache\Reflector
 * @author  Loo Say Phoon <sploo@alphline.com>
 */
class FunctionCache implements HashableReflectorInterface
{
    use ReflectorTrait;
    
    private $database;        
    private $class;
    private $function;
    private $arguments;
    private $tables;
    private $rows;


    /**
     * Initialize reflector.
     *
     * @param EloquentModel $model
     * @param string $function
     * @param array $parameters
     * @param array $tables
     */
    public function __construct(string $database, string $class, string $function, array $arguments, array $tables, array $rows)
    { 
        $this->database = $database;
        $this->class = $class;
        $this->function = $function;
        $this->arguments = $arguments;
        $this->tables = $tables;  
        $this->rows = $rows;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getTables()
    {
        return $this->tables;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getRows()
    {
        return $this->rows;
    }


    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getSql()
    {
        return $this->class . "\\" .  $this->function;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getParameters()
    {
        return $this->arguments;
    }

}