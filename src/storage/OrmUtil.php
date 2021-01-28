<?php
namespace phpformsframework\libs\storage;

use Exception;

/**
 * Trait OrmUtil
 * @package phpformsframework\libs\storage
 */
trait OrmUtil
{
    /**
     * @var Model|null
     */
    private $db                                                                 = null;
    private $primaryKey                                                         = null;
    private $recordKey                                                          = null;



    /**
     * @param array $errors
     * @param string|null $suffix
     * @throws Exception
     */
    private function error(array $errors, string $suffix = null) : void
    {
        throw new Exception($this->db->getName() .  "::db->" . ($this->recordKey ? "update" : "insert") . ": " . implode(", ", $errors) . $suffix, 400);
    }
}
