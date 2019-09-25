<?php
namespace phpformsframework\libs\tpl;

interface ViewAdapter
{
    const ERROR_BUCKET = "template";

    /**
     * @param string $file_disk_path
     * @return $this
     */
    public function fetch($file_disk_path);

    /**
     * @param array|string|callable $data
     * @param null|string $value
     * @return $this
     */
    public function assign($data, $value = null);

    /**
     * @return string
     */
    public function display();

    /**
     * @param string$sectionName
     * @param bool $repeat
     * @return $this
     */
    public function parse($sectionName, $repeat = false);

    /**
     * @param string $name
     * @return bool
     */
    public function isset($name);
}
