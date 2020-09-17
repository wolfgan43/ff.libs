<?php
namespace phpformsframework\libs\dto;

/**
 * Class ConfigPage
 * @package phpformsframework\libs\dto
 *
 * name
 * scope
 * hide
 * default
 * validator
 * validator_range
 * validator_mime
 * required
 * required_ifnot
 *
 * vpn**
 * auth**
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

    /**
     * @param array $pageRules
     */
    public function set(array $pageRules) : void
    {
        if (isset($pageRules["header"])) {
            $this->setVar($this->header, $pageRules["header"]);
        }
        if (isset($pageRules["query"])) {
            $this->setVar($this->query, $pageRules["query"]);
        }
        if (isset($pageRules["body"])) {
            $this->setVar($this->body, $pageRules["body"]);
        }
    }

    /**
     * @param array|null $rules
     * @param array $vars
     */
    private function setVar(array &$vars, array $rules = null) : void
    {
        $vars = array_replace((array) $rules, $vars);
    }
}
