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

class Bootstrap4 extends FrameworkCssAdapter {
    protected $css          = array(
                                "bootstrap"                 => "https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.3.1/css/bootstrap.min.css"
                            );
    protected $js           = array(
                                "jquery"                    => "https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.0/jquery.min.js"
                                , "popper"                  => "https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js"
                                , "bootstrap"               => "https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.3.1/js/bootstrap.min.js"
                            );
    protected $skip_resolution                              = true;
    protected $skip_resolution_full                         = true;
    protected $skip_prepost                                 = "nopadding";

    protected $container                                    = "container";
    protected $container_fluid                              = "container-fluid";
    protected $wrap                                         = "row";

    protected $row          = array(
                                "prefix"                    => "row"
                                , "start"                   => "row justify-content-start"
                                , "end"                     => "row justify-content-end"
                                , "center"                  => "row justify-content-center"
                                , "between"                 => "row justify-content-between"
                                , "around"                  => "row justify-content-around"
                                , "padding"                 => "row mb-3"
                            );
    protected $col          = array(
                                "append"                    => "col-"
                                , "hidden"                  => "hidden-"
                                , "hidden-smallest"         => ""
                                , "hidden-largest"          => ""
                            );
    protected $push         = array(
                                "append"                    => "col-"
                                , "prepend"                 => "push-"
                            );
    protected $pull         = array(
                                "append"                    => "col-"
                                , "prepend"                 => "pull-"
                            );
    protected $resolution   = array(
                                ""
                                , "sm"
                                , "md"
                                , "lg"
                                , "xl"
                            );
   /* protected $resolution_media = array(
                                "" => "(min-width:34em)"
                                , "sm" => "(min-width:48em)"
                                , "md" => "(min-width:62em)"
                                , "lg" => "(min-width:75em)"
                                , "xl" => "(min-width:85em)"
                            );*/
    protected $button       = array(
                                "prepend"                   => "btn"
                                , "append"                  => ""
                                , "skip-default"            => true
                                , "width"                   => array(
                                    "full"                  => "btn-block"
                                )
                                , "size"                    => array(
                                    "large"                 => "btn-lg"
                                    , "small"               => "btn-sm"
                                    , "tiny"                => "btn-xs"
                                )
                                , "state"                   => array(
                                    "current"               => "active"
                                    , "disabled"            => "disabled"
                                )
                                , "corner"                  => array(
                                    "round"                 => false
                                    , "radius"              => false
                                )
                                , "color"                   => array(
                                    "default"               => "btn-default"
                                    , "primary"             => "btn-primary"
                                    , "secondary"           => "btn-secondary"
                                    , "success"             => "btn-success"
                                    , "info"                => "btn-info"
                                    , "warning"             => "btn-warning"
                                    , "danger"              => "btn-danger"
                                    , "link"                => "btn-link"
                                )
                            );
    protected $form         = array(
                                "component"                 => ""
                                , "component-inline"        => ""
                                , "row"                     => "form-group"
                                , "row-inline"              => "form-group row"
                                , "row-check"               => "form-group form-check"
                                , "row-padding"             => "form-group padding"
                                , "row-full"                => "form-group"
                                , "group"                   => "input-group"
                                , "group-sm"                => "input-group input-group-sm"
                                , "group-padding"           => "input-group padding"
                                , "label"                   => ""
                                , "label-inline"            => "col-form-label"
                                , "label-check"             => "form-check-label"
                                , "control"                 => "form-control"
                                , "control-check"           => "form-check-control"
                                , "control-file"            => "form-control-file"
                                , "control-plaintext"       => "form-control-plaintext"
                                , "size-sm"                 => "form-control-sm"
                                , "size-lg"                 => "form-control-lg"
                                , "control-exclude"         => array("checkbox", "radio")
                                , "control-prefix"          => "input-group-prepend"
                                , "control-postfix"         => "input-group-append"
                                , "control-text"            => "input-group-text"
                                , "control-feedback"        => "form-control-feedback"
                                , "wrap"                    => "form-row"
                                , "wrap-padding"            => "form-row pl-1 pr-2"
                            );
    protected $bar          = array(
                                "topbar"                    => "nav navbar-nav"
                                , "navbar"                  => "nav nav-pills nav-stacked"
                                , "sidebar"                 => array(
                                    ///todo: da fare il merge con il tema custom
                                )
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
                                , "primary"                 => "badge badge-primary"
                                , "success"                 => "badge badge-success"
                                , "info"                    => "badge badge-info"
                                , "warning"                 => "badge badge-warning"
                                , "danger"                  => "badge badge-danger"
                            );
    protected $callout      = array(
                                "default"                   => "alert alert-secondary"
                                , "primary"                 => "alert alert-primary"
                                , "success"                 => "alert alert-success"
                                , "info"                    => "alert alert-info"
                                , "warning"                 => "alert alert-warning"
                                , "danger"                  => "alert alert-danger"
                            );
    protected $pagination   = array(
                                "align-left"                => "text-left"
                                , "align-center"            => "text-center"
                                , "align-right"             => "text-right"
                                , "pages"                   => "pagination"
                                , "arrows"                  => ""
                                , "page"                    => "page-item"
                                , "page-link"               => "page-link"
                                , "current"                 => "active"
                            );
    protected $table        = array(
                                "container"                 => "table"
                                , "inverse"                 => "table-dark"
                                , "compact"                 => "table-condensed"
                                , "small"                   => "table-sm"
                                , "hover"                   => "table-hover"
                                , "border"                  => "table-bordered"
                                , "oddeven"                 => "table-striped"
                                , "responsive"              => "table-responsive"
                                , "sorting"                 => "sorting"
                                , "sorting_asc"             => "sorting_asc"
                                , "sorting_desc"            => "sorting_desc"
                            );
    protected $tab          = array(
                                "menu"                      => "nav nav-tabs"
                                , "menu-pills"              => "nav nav-pills"
                                , "menu-pills-justified"    => "nav nav-pills nav-justified"
                                , "menu-bordered"           => "nav nav-tabs nav-bordered"
                                , "menu-bordered-justified" => "nav nav-tabs nav-bordered nav-justified"
                                , "menu-vertical"           => "nav flex-column nav-pills"
                                , "menu-vertical-right"     => "nav flex-column nav-pills"
                               // , "menu-vertical-wrap"    => true
                                , "menu-item"               => "nav-item"
                                , "menu-item-link"          => "nav-link"
                                , "menu-current"            => "active show"
                                , "pane"                    => "tab-content"
                                , "pane-item"               => "tab-pane"
                                , "pane-current"            => "active show"
                                , "pane-item-effect"        => "tab-pane fade"
                                , "pane-current-effect"     => "active show"
                            );
    protected $collapse     = array(
                                "pane" 				        => "collapse"
                                , "current" 		        => "in"
                                , "menu" 			        => "collapsed"
                            );
    protected $tooltip      = array(
                                "elem"                      => "has-tip"
                            );
    protected $dialog       = array(
                                "overlay"                   => "modal"
                                , "window"                  => "modal-dialog"
                                , "window-center"           => "modal-dialog-centered"
                                , "window-small"            => "modal-sm"
                                , "window-large"            => "modal-lg"
                                , "window-huge"             => "modal-xl"
                                , "container"               => "modal-content"
                                , "header"                  => "modal-header"
                                , "body"                    => "modal-body"
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
                                "left"                      => "pull-left"
                                , "right"                   => "pull-right"
                                , "hide"                    => "d-none"
                                , "align-left"              => "text-left"
                                , "align-center"            => "text-center"
                                , "align-right"             => "text-right"
                                , "align-justify"           => "text-justify"
                                , "text-nowrap"             => "text-nowrap"
                                , "text-overflow"           => "text-overflow"
                                , "text-lowercase"          => "text-lowercase"
                                , "text-uppercase"          => "text-uppercase"
                                , "text-capitalize"         => "text-capitalize"

                                , "text-muted"              => "text-muted"
                                , "text-primary"            => "text-primary"
                                , "text-success"            => "text-success"
                                , "text-info"               => "text-info"
                                , "text-warning"            => "text-warning"
                                , "text-danger"             => "text-danger"

                                , "bg-primary"              => "bg-primary"
                                , "bg-success"              => "bg-success"
                                , "bg-info"                 => "bg-info"
                                , "bg-warning"              => "bg-warning"
                                , "bg-danger"               => "bg-danger"

                                , "current"                 => "active"
                                , "equalizer-row"           => "data-equalizer"
                                , "equalizer-col"           => "data-equalizer-watch"
                                , "corner-radius"           => "border-radius"
                                , "corner-round"            => "img-rounded"
                                , "corner-circle"           => "img-circle"
                                , "corner-thumbnail"        => "img-thumbnail"
                                , "clear"                   => "clearfix"
                            );
    protected $data         = array(
                                "tab"                       => array(
                                    "menu"                  => null
                                    , "menu-link"           => 'data-toggle="tab"'
                                    , "pane"                => null
                                    , "pane-item"           => null
                                )
                                , "tooltip"                 => array(
                                    "elem"                  => 'data-toggle="tooltip"'
                                )
                                , "collapse"                => array(
                                    "link"                  => 'data-toggle="collapse"'
                                )
                                , "button"                  => array(
                                    "toggle"                => 'data-toggle="button"'
                                )
                            );
}

