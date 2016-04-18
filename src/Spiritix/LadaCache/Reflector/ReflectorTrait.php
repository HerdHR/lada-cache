<?php namespace Spiritix\LadaCache\Reflector;

trait ReflectorTrait {

	private function resolveTable($table) {        
        $viewTables = (array)config('lada-cache.view-tables');
        $tableName = $this->resolveTableName($table);

        if(array_key_exists($tableName, $viewTables)) {
            return $viewTables[$tableName];
        }

        return [$tableName];
    }


    private function resolveTableName($tableName) {
        $matches = [];
        $pattern = '/^(?<TableName>\w+) AS (?<AliasName>\w+)/';
        
        if (preg_match ($pattern, $tableName, $matches)) {            
            return $matches["TableName"];
        }
        return $tableName;
    }
}
