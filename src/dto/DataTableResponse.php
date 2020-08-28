<?php
namespace phpformsframework\libs\dto;

/**
 * Class DataTableResponse
 * @package phpformsframework\libs\dto
 */
class DataTableResponse extends DataResponse
{
    public $draw                = 0;
    public $recordsTotal        = 0;
    public $recordsFiltered     = 0;


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
}
