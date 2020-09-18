<?php
namespace phpformsframework\libs\storage;

/**
 * Trait DataAccess
 * @package phpformsframework\libs\storage
 */
trait DatabaseManager
{
    /**
     * @param string|null $collection
     * @param string|null $mainTable
     * @return Orm
     */
    public static function orm(string $collection = null, string $mainTable = null) : Orm
    {
        return Model::orm($collection, $mainTable);
    }
}
