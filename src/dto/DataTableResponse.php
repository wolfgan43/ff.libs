<?php
namespace phpformsframework\libs\dto;

/**
 * Class DataTableResponse
 * @package phpformsframework\libs\dto
 */
class DataTableResponse extends DataResponse
{
    public $draw                = 1;
    public $recordsTotal        = null;
    public $recordsFiltered     = null;


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