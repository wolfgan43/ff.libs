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
     * @param string|DataHtml|View|Controller $content
     * @param string|null $where
     * @return $this
     */
    abstract public function addContent($content, string $where = null) : self;

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


    /**
     * @param array|null $css
     * @param array|null $style
     * @param array|null $fonts
     * @param array|null $js
     * @param array|null $js_embed
     * @param array|null $js_template
     * @param array|null $structured_data
     * @param array|null $meta
     * @param string|null $body_class
     * @param string|null $doc_type
     * @return self
     */
    public function includeAssets(
        array   $css                = null,
        array   $style              = null,
        array   $fonts              = null,
        array   $js                 = null,
        array   $js_embed           = null,
        array   $js_template        = null,
        array   $structured_data    = null,
        array   $meta               = null,
        string  $body_class         = null,
        string  $doc_type           = null
    ) : void {
        $this->css                  = $this->css                + $css;
        $this->style                = $this->style              + $style;
        $this->fonts                = $this->fonts              + $fonts;
        $this->js                   = $this->js                 + $js;
        $this->js_embed             = $this->js_embed           + $js_embed;
        $this->js_template          = $this->js_template        + $js_template;
        $this->structured_data      = $this->structured_data    + $structured_data;

        $this->meta                 = array_replace($this->meta, $meta);
        $this->body_class           = $body_class;
        $this->doc_type             = $doc_type;
    }
}