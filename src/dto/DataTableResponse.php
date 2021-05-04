<?php
namespace phpformsframework\libs\dto;

use stdClass;

/**
 * Class DataTableResponse
 * @package phpformsframework\libs\dto
 */
class DataTableResponse extends DataResponse
{
    public $draw                = 0;
    public $recordsTotal        = 0;
    public $recordsFiltered     = 0;



    public function __construct(array $data = array())
    {
        parent::__construct($data);

        $this->draw             = 1;
        $this->recordsFiltered  = count($data);
        $this->recordsTotal     = count($data);
    }

    /**
     * @return array
     */
    protected function getDefaultVars() : array
    {
        return [
            "draw"              => $this->draw,
            "data"              => $this->data,
            "recordsTotal"      => $this->recordsTotal,
            "recordsFiltered"   => $this->recordsFiltered,
            "error"             => $this->error,
            "status"            => $this->status
        ];
    }

    /**
     * @param string $name
     * @return array
     */
    public function getColumn(string $name) : array
    {
        return array_column($this->data ?? [], $name);
    }

    /**
     * @return array|stdClass
     */
    public function toObject()
    {
        return parent::toObject() ?? [];
    }
}
