<?php
namespace phpformsframework\libs\dto;

use ArrayObject;

/**
 * Class Dto
 * @package phpformsframework\libs\dto
 */
class Dto
{
    use Mapping;

    private static $page = null;

    public function __construct(RequestPage $page, bool $fill_undefined_properties = false)
    {
        self::$page =& $page;

        if ($fill_undefined_properties) {
            $this->autoMappingMagic(self::$page->getRequest());
        } else {
            $this->autoMapping(self::$page->getRequest());
        }
    }

    public function getRawData() : array
    {
        return self::$page->getRawData();
    }
}