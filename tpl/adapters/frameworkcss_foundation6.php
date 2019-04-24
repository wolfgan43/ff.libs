<?php
/**
 * VGallery: CMS based on FormsFramework
 * Copyright (C) 2004-2015 Alessandro Stucchi <wolfgan@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 *  @package VGallery
 *  @subpackage core
 *  @author Alessandro Stucchi <wolfgan@gmail.com>
 *  @copyright Copyright (c) 2004, Alessandro Stucchi
 *  @license http://opensource.org/licenses/gpl-3.0.html
 *  @link https://github.com/wolfgan43/vgallery
 */
namespace phpformsframework\libs\tpl\gridsystem;

class Foundation6 extends FrameworkCssAdapter {
    protected $css          = array(
                                "bootstrap"                 => "https://cdnjs.cloudflare.com/ajax/libs/foundation/6.2.3/foundation.min.css"
                            );
    protected $js           = array(
                                "jquery"                    => "https://code.jquery.com/jquery-3.3.1.slim.min.js"
                                , "bootstrap"               => "https://cdnjs.cloudflare.com/ajax/libs/foundation/6.2.3/foundation.min.js"
                            );
    protected $skip_resolution                              = true;
    protected $skip_resolution_full                         = true;
    protected $skip_prepost                                 = "nopadding";

    protected $container                                    = "container";
    protected $container_fluid                              = "container-fluid";
    protected $wrap                                         = "row";

    protected $row          = array(
                                "prefix"                    => "row"
                                , "start"                   => "row"
                                , "end"                     => "row"
                                , "center"                  => "row"
                                , "between"                 => "row"
                                , "around"                  => "row"
                                , "padding"                 => "row padding"
                            );
    protected $col          = array(
                                "prefix"                    => "columns"
                                , "hidden"                  => "hide-for-"
                                , "hidden-smallest"         => ""
                                , "hidden-largest"          => "-up"
                            );
    protected $push         = array(
                                "append"                    => ""
                                , "prepend"                 => "push-"
                            );
    protected $pull         = array(
                                "append"                    => ""
                                , "prepend"                 => "pull-"
                            );
    protected $resolution   = array(
                                "small"
                                , "medium"
                                , "large"
                            );
   /* protected $resolution_media = array(
                                "small" => "(max-width: 40em)"
                                , "medium" => "(min-width: 40.063em)"
                                , "large" => "(min-width: 64.063em)"
                            );*/
    protected $button       = array(
                                "prepend"                   => "btn"
                                , "append"                  => ""
                                , "skip-default"            => true
                                , "width"                   => array(
                                    "full"                  => "expand"
                                )
                                , "size"                    => array(
                                    "large"                 => "large"
                                    , "small"               => "small"
                                    , "tiny"                => "tiny"
                                )
                                , "state"                   => array(
                                    "current"               => "current"
                                    , "disabled"            => "disabled"
                                )
                                , "corner"                  => array(
                                    "round"                 => "round"
                                    , "radius"              => "radius"
                                )
                                , "color"                   => array(
                                    "default"               => "secondary"
                                    , "primary"             => "primary"
                                    , "secondary"           => "primary"
                                    , "success"             => "success"
                                    , "info"                => "secondary"
                                    , "warning"             => "alert"
                                    , "danger"              => "alert"
                                    , "link"                => "secondary"
                                )
                            );
    protected $form         = array(
                                "component"                 => ""
                                , "component-inline"        => ""
                                , "row"                     => "row"
                                , "row-inline"              => "row"
                                , "row-check"               => "row"
                                , "row-padding"             => "row padding"
                                , "row-full"                => "columns"
                                , "group"                   => "row collapse"
                                , "group-sm"                => "row"
                                , "group-padding"           => "row collapse padding"
                                , "label"                   => ""
                                , "label-inline"            => "inline right"
                                , "label-check"             => "inline"
                                , "control"                 => ""
                                , "control-check"           => ""
                                , "control-file"            => ""
                                , "control-plaintext"       => ""
                                , "size-sm"                 => "small"
                                , "size-lg"                 => "large"
                                , "control-exclude"         => array()
                                , "control-prefix"          => "prefix"
                                , "control-postfix"         => "postfix"
                                , "control-text"            => ""
                                , "control-feedback"        => "postfix-feedback"
                                , "wrap"                    => "row"
                                , "wrap-padding"            => "row padding"
                            );
    protected $bar          = array(
                                "topbar"                    => "top-bar top-bar-section"
                                , "navbar"                  => "side-nav"
                            );
    protected $sidenav      = array(
                                "container"                 => "side-nav in"
                                , "item"                    => "side-nav-item"
                                , "link"                    => "side-nav-link"
                                , "secondlevelmenu"         => "side-nav-second-level"
                                , "thirdlevelmenu"          => "side-nav-third-level"
                                , "header"                  => "side-nav-title side-nav-item"
                             );
    protected $list         = array(
                                "group"                     => "list-group"
                                , "group-horizontal"        => "list-group list-group-horizontal"
                                , "item"                    => "list-group-item"
                                , "item-button"             => "list-group-item-action"
                                , "item-success"            => "list-group-item-success"
                                , "item-info"               => "list-group-item-info"
                                , "item-warning"            => "list-group-item-warning"
                                , "item-danger"             => "list-group-item-danger"
                                , "current"                 => "active"
                                , "disabled"                => "disabled"
                            );
    protected $topbar       = array(
                                "container"                 => "navbar navbar-default"
                                , "container-fixed-top"     => "navbar navbar-default navbar-fixed-top"
                                , "container-fixed-bottom"  => "navbar navbar-default navbar-fixed-bottom"
                                , "header"                  => "navbar-header"
                                , "nav-brand"               => "navbar-brand"
                                , "nav-form"                => "navbar-form navbar-left"
                                , "nav-right"               => "navbar-right"
                                , "dropdown"                => "dropdown-toggle"
                                , "dropdown-menu"           => "dropdown-menu"
                                , "current"                 => "active"
                                , "hamburger"               => "navbar-toggle"
                            );
    protected $dropdown     = array(
                                "container" 	            => "dropdown"
                                , "menu" 		            => "dropdown-menu"
                                , "sub-menu" 	            => "dropdown-submenu"
                                , "header" 		            => "dropdown-header"
                                , "sep" 		            => "divider"
                                , "opened"		            => "open"
                                , "current" 	            => "active"
                            );
    protected $panel        = array(
                                "container"                 => "panel panel-default"
                                , "container-primary"       => "panel-primary"
                                , "container-success"       => "panel-success"
                                , "container-info"          => "panel-info"
                                , "container-warning"       => "panel-warning"
                                , "container-danger"        => "panel-danger"
                                , "heading"                 => "panel-heading"
                                , "body"                    => "panel-body"
                                , "footer"                  => "panel-footer"
                            );
    protected $badge        = array(
                                "default"                   => "badge"
                                , "primary"                 => "badge primary"
                                , "success"                 => "badge success"
                                , "info"                    => "badge info"
                                , "warning"                 => "badge warning"
                                , "danger"                  => "badge danger"
                            );
    protected $callout      = array(
                                "default"                   => "panel"
                                , "primary"                 => "alert-box"
                                , "success"                 => "alert-box success"
                                , "info"                    => "panel callout"
                                , "warning"                 => "alert-box warning"
                                , "danger"                  => "alert-box alert"
                            );
    protected $pagination   = array(
                                "align-left"                => "text-left"
                                , "align-center"            => "pagination-centered" //"text-center"
                                , "align-right"             => "text-right"
                                , "pages"                   => "pagination"
                                , "arrows"                  => "arrow"
                                , "page"                    => ""
                                , "page-link"               => ""
                                , "current"                 => "current"
                            );
    protected $table        = array(
                                "container"                 => ""
                                , "inverse"                 => "table-dark"
                                , "compact"                 => "table-condensed"
                                , "small"                   => "table table-sm"
                                , "hover"                   => "table-hover"
                                , "border"                  => "table-bordered"
                                , "oddeven"                 => "table-striped"
                                , "responsive"              => ""
                                , "sorting"                 => "sorting"
                                , "sorting_asc"             => "sorting_asc"
                                , "sorting_desc"            => "sorting_desc"
                            );
    protected $tab          = array(
                                "menu"                      => "tabs"
                                , "menu-pills"              => "pills"
                                , "menu-pills-justified"    => "pills justified"
                                , "menu-bordered"           => "tabs bordered"
                                , "menu-bordered-justified" => "tabs bordered justified"
                                , "menu-vertical"           => "tabs vertical"
                                , "menu-vertical-right"     => "tabs vertical right"
                                , "menu-item"               => "tabs-title"
                                , "menu-item-link"          => ""
                                , "menu-current"            => "is-active"
                                , "pane"                    => "tabs-content"
                                , "pane-item"               => "tabs-panel"
                                , "pane-current"            => "is-active"
                                , "pane-item-effect"        => "tabs-panel fade"
                                , "pane-current-effect"     => "is-active"
                            );
    protected $collapse     = array(
                                "pane" 				        => "collapse" // da trovare analogo per foundation
                                , "current" 		        => "in" // da trovare analogo per foundation
                                , "menu" 			        => "collapsed"
                            );
    protected $tooltip      = array(
                                "elem"                      => "has-tip"
                            );
    protected $dialog       = array(
                                "overlay"                   => "reveal-overlay"
                                , "window"                  => "reveal"
                                , "window-center"           => "centered"
                                , "window-small"            => "tiny"
                                , "window-large"            => "large"
                                , "window-huge"             => "full"
                                , "container"               => "modal-content"
                                , "header"                  => "modal-header"
                                , "content"                 => "modal-body"
                                , "footer"                  => "modal-footer"
                                , "title"                   => "modal-title"
                                , "subtitle"                => "modal-subtitle"
                                , "button"                  => "close"
                                , "effect"                  => "fade"
                            );
    protected $card         = array(
                                "container"                 => "card"
                                , "cover-top"               => "card-img-top"
                                , "header"                  => "card-header"
                                , "body"                    => "card-body"
                                , "footer"                  => "card-footer"
                                , "title"                   => "card-title"
                                , "sub-title"               => "card-subtitle"
                                , "text"                    => "card-text"
                                , "link"                    => "card-link"
                                , "list-group"              => "list-group list-group-flush"
                                , "list-group-item"         => "list-group-item"
                            );
    protected $util         = array(
                                "left"                      => "float-left"
                                , "right"                   => "float-right"
                                , "hide"                    => "hide"
                                , "align-left"              => "text-left"
                                , "align-center"            => "text-center"
                                , "align-right"             => "text-right"
                                , "align-justify"           => "text-justify"
                                , "text-nowrap"             => "text-nowrap"
                                , "text-overflow"           => "text-overflow"         //custom
                                , "text-lowercase"          => "text-lowercase"         //custom
                                , "text-uppercase"          => "text-uppercase"         //custom
                                , "text-capitalize"         => "text-capitalize"    //custom

                                , "text-muted"              => "text-muted" //custom
                                , "text-primary"            => "text-primary" //custom
                                , "text-success"            => "text-success" //custom
                                , "text-info"               => "text-info" //custom
                                , "text-warning"            => "text-warning" //custom
                                , "text-danger"             => "text-danger" //custom

                                , "bg-primary"              => "bg-primary" //custom
                                , "bg-success"              => "bg-success" //custom
                                , "bg-info"                 => "bg-info" //custom
                                , "bg-warning"              => "bg-warning" //custom
                                , "bg-danger"               => "bg-danger" //custom

                                , "current"                 => "active"
                                , "equalizer-row"           => "data-equalizer"
                                , "equalizer-col"           => "data-equalizer-watch"
                                , "corner-radius"           => "radius"
                                , "corner-round"            => "round"
                                , "corner-circle"           => "img-circle"
                                , "corner-thumbnail"        => "img-thumbnail"
                                , "clear"                   => "clearfix"
                            );
    protected $data         = array(
                                "tab"                       => array(
                                    "menu"                  => 'data-tabs'
                                    , "menu-link"           => null
                                    , "pane"                => 'data-tabs-content'
                                    , "pane-item"           => null
                                ),
                                "tooltip"                   => array(
                                    "elem"                  => "data-tooltip"
                                )
                                , "collapse"                => array(
                                    "link"                  => 'data-toggle' // da trovare analogo per foundation
                                )
                                , "button"                  => array(
                                    "toggle"                => 'data-toggle'
                                )
                            );
}

