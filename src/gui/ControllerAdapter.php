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
    public const ASSET_LOCATION_HEAD            = "Head";
    public const ASSET_LOCATION_BODY_TOP        = "BodyTop";
    public const ASSET_LOCATION_BODY_BOTTOM     = "BodyBottom";

    public const MEDIA_DEVICE_ALL               = "all";
    public const MEDIA_DEVICE_PRINT             = "print";
    public const MEDIA_DEVICE_SCREEN            = "screen";
    public const MEDIA_DEVICE_SPEECH            = "speech";

    public $css                                 = [];
    public $style                               = [];
    public $fonts                               = [];
    public $js                                  = [];
    public $js_embed                            = [];
    public $js_template                         = [];
    public $structured_data                     = [];

    public $meta                                = [];

    public $doc_type                            = null;
    public $body_class                          = null;

    /**
     * @param string $tpl_var
     * @param string|DataHtml|View|Controller $content
     * @return $this
     */
    abstract public function assign($tpl_var, $content = null) : self;

    /**
     * @param string|null $template_or_html
     * @param string|null $theme
     * @return $this
     */
    abstract public function setLayout(string $template_or_html = null, string $theme = null) : self;

    /**
     * @param int|null $http_status_code
     */
    abstract public function display(int $http_status_code = null) : void;

    /**
     * @return string
     */
    abstract public function toHtml() : string;
}
