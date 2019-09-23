<?php
namespace phpformsframework\libs\dto;

/**
 * Class ConfigPage
 * @package phpformsframework\libs\dto
 */
class RequestPageRules
{
    public $header          = array();
    public $query           = array();
    public $body            = array();
    public $last_update     = null;

    public function __construct()
    {
        $this->last_update  = microtime(true);
    }

    public function set($pageRules)
    {
        if (isset($pageRules["header"])) {
            $this->setVar($pageRules["header"], $this->header);
        }
        if (isset($pageRules["query"])) {
            $this->setVar($pageRules["query"], $this->query);
        }
        if (isset($pageRules["body"])) {
            $this->setVar($pageRules["body"], $this->body);
        }
    }

    private function setVar($rules, &$vars)
    {
        $vars = array_replace((array) $rules, $vars);
    }
}
