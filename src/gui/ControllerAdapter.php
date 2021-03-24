<?php
namespace phpformsframework\libs\gui;

use phpformsframework\libs\dto\DataHtml;
use phpformsframework\libs\Mappable;

/**
 * Class ControllerAdapter
 * @package phpformsframework\libs\gui
 */
abstract class ControllerAdapter extends Mappable
{
    /**
     * Assets Method
     */
    public const ASSET_LOCATION_ASYNC           = "Async";
    public const ASSET_LOCATION_DEFER           = "Defer";
    public const ASSET_LOCATION_HEAD            = "Head";
    public const ASSET_LOCATION_BODY_TOP        = "BodyTop";
    public const ASSET_LOCATION_BODY_BOTTOM     = "BodyBottom";

    public const MEDIA_DEVICE_ALL               = "all";
    public const MEDIA_DEVICE_PRINT             = "print";
    public const MEDIA_DEVICE_SCREEN            = "screen";
    public const MEDIA_DEVICE_SPEECH            = "speech";

    /**
     * @param string $tpl_var
     * @param string|DataHtml|View|Controller $content
     * @return $this
     */
    abstract public function assign(string $tpl_var, $content = null) : self;

}
