<?php
namespace phpformsframework\libs\gui;

/**
 * Interface ViewAdapter
 * @package phpformsframework\libs\gui
 */
interface ViewAdapter
{
    const ERROR_BUCKET = "template";

    /**
     * @param string $file_disk_path
     * @return $this
     */
    public function fetch(string $file_disk_path) : ViewAdapter;

    /**
     * @param array|string|callable $data
     * @param mixed|null $value
     * @return $this
     */
    public function assign($data, $value = null) : ViewAdapter;

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
