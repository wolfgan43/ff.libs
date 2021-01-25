<?php
namespace phpformsframework\libs\gui\adapters;

/**
 * Interface ViewAdapter
 * @package phpformsframework\libs\gui
 */
interface ViewAdapter
{
    const ERROR_BUCKET = "template";

    public function __construct();

    /**
     * @param string $template_disk_path
     * @return $this
     */
    public function fetch(string $template_disk_path) : ViewAdapter;

    /**
     * @param array|string|callable $tpl_var
     * @param mixed|null $value
     * @return $this
     */
    public function assign($tpl_var, $value = null) : ViewAdapter;

    /**
     * @return string
     */
    public function display() : string;

    /**
     * @param string $sectionName
     * @param bool $repeat
     * @param bool $appendBefore
     * @return bool
     */
    public function parse(string $sectionName, bool $repeat = false, bool $appendBefore = false) : bool;

    /**
     * @param string $name
     * @return bool
     */
    public function isset(string $name) : bool;
}
