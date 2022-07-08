<?php
/**
 * Library for WebApplication based on VGallery Framework
 * Copyright (C) 2004-2021 Alessandro Stucchi <wolfgan@gmail.com>
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
 *  @subpackage libs
 *  @author Alessandro Stucchi <wolfgan@gmail.com>
 *  @copyright Copyright (c) 2004, Alessandro Stucchi
 *  @license http://opensource.org/licenses/lgpl-3.0.html
 *  @link https://bitbucket.org/cmsff/libs
 */
namespace ff\libs\gui\components;

use ff\libs\dto\DataHtml;
use ff\libs\dto\DataTableResponse;
use ff\libs\international\Translator;
use ff\libs\Kernel;
use ff\libs\microservice\Api;
use ff\libs\storage\dto\OrmResults;
use ff\libs\storage\Model;
use ff\libs\Exception;
use stdClass;

/**
 * Class DataTable
 * @package ff\libs\gui\components
 */
class DataTable
{
    /**
     * Token Class
     */
    private const DIR                   = ["asc" => "asc", "desc" => "desc", 0 => "asc", 1 => "desc", null => "asc"];
    private const RDIR                  = ["asc" => "desc", "desc" => "asc", null => "asc"];

    private const TC_SORT               = "sort";
    private const TC_ODD                = "odd";
    private const TC_EVEN               = "even";

    private const TC_PAGE               = "page";
    private const TC_NEXT               = "next";
    private const TC_PREV               = "prev";

    private const TC_CURRENT            = "current";
    private const TC_SELECTED           = "selected";

    private const TC_ERROR              = "cm-error";
    private const TC_XHR                = "cm-xhr";
    private const TC_MODAL              = "cm-modal";

    private const DEFAULT_SORT_DIR      = "asc";

    private const RECORD_LIMIT          = 25;

    protected const RECORD_KEY          = "id";

    protected const CONVERT_BY_TYPE     = [
                                            "image"     => [
                                                "callback"  => "imageTag",
                                                "width"     => 50,
                                                "height"    => 50,
                                                "default"   => "noimg"
                                            ],
                                            "datetime"  => [],
                                            "date"      => [],
                                            "time"      => [],
                                            "week"      => [],
                                            "currency"  => []
                                        ];
    protected const BUTTONS             = [
                                            "header" => [
                                                "addnew"    => [
                                                    "type"          => Button::TYPE_PRIMARY,
                                                    "label"         => "AddNew",
                                                    "icon"          => "fa fa-plus",
                                                    "url"           => "addnew"
                                                ]
                                            ],
                                            "record" => [
                                                "modify"    => [
                                                    "placeholder"   => "Modify",
                                                    "icon"          => "fa fa-pencil-alt",
                                                    "url"           => "modify"
                                                ],
                                                "delete"    => [
                                                    "placeholder"   => "Delete",
                                                    "icon"          => "fa fa-trash",
                                                    "url"           => "delete",
                                                    "hide"          => true
                                                ]
                                            ],
                                            "footer" => []
                                        ];

    protected const TEMPLATE_CLASS      = [];

    protected const CSS                 = [
                                            "cm.dataTable"
                                        ];

    protected const JS                  = [
                                            "cm.dataTable"
                                        ];

    private const DATA_SOURCE_ORM       = "orm";
    private const DATA_SOURCE_API       = "api";
    private const DATA_SOURCE_ARRAY     = "array";
    private const DATA_SOURCE_TABLE     = "datatable";
    private const DATA_SOURCE_SQL       = "sql";
    private const DATA_SOURCE_NOSQL     = "nosql";

    private $template                    = '
                                            <div class="dt-header"> 
                                                [TITLE][ACTION_HEADER]
                                            </div>
                                            [DESCRIPTION]
                                            <div class="dt-body">
                                                [ERROR]
                                                <div class="dataTable-wrapper">
                                                    <div class="dataTable-top">
                                                        [LENGTH]
                                                        [SEARCH]
                                                    </div>
                                                    <div class="dataTable-container">
                                                        <table class="table dataTable-table">
                                                            [THEAD]
                                                            [TBODY]
                                                            [TFOOT]
                                                        </table>
                                                    </div>
                                                    <div class="dataTable-bottom">
                                                        [PAGINATE_INFO]
                                                        [PAGINATE]
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="dt-footer">
                                                [FOOTER][ACTION_FOOTER]
                                            </div>
                                           ';


    public $displayTableHead            = true;
    public $displayTableFoot            = false;

    public $displayTableLength          = true;
    public $displayTableSearch          = true;
    public $displayTableSort            = true;

    public $displayTablePaginate        = true;
    public $displayTablePaginateInfo    = true;

    protected $xhr                      = null;
    private $xhrSearch                  = null;
    private $xhrSort                    = null;
    private $xhrPagination              = null;

    public $lengths                     = [10, 25, 50];

    private $title                      = null;
    private $description                = null;
    public $error                       = null;

    /**
     * @var DataTableResponse
     */
    protected $dataTable                = null;

    protected $id                       = null;
    private $api                        = null;

    /**
     * @var Button[]
     */
    private $buttonsHeader              = [];
    /**
     * @var Button[]
     */
    private $buttonsRecord              = [];
    /**
     * @var Button[]
     */
    private $buttonsFooter              = [];

    /**
     * @var DataTableColumn[]
     */
    private $columns                    = [];

    protected $draw                     = null;
    protected $start                    = null;
    protected $length                   = null;
    protected $search                   = null;
    protected $sort                     = [];

    private $request                    = null;
    private $pages                      = null;
    private $page                       = null;

    protected $style                    = [];
    protected $js_embed                 = null;
    protected $js_tpl                   = null;

    protected $record_key               = null;

    private $dataSource                 = self::DATA_SOURCE_ARRAY;
    private $records                    = [];

    private $default_sort               = null;
    private $default_sort_dir           = null;

    /**
     * @param string $model
     * @return DataTableResponse
     */
    public static function xhr(string $model): DataTableResponse
    {
        $dt = new static();

        $component = explode(":", $model);
        $dataSource = array_pop($component);
        if (empty($component)) {
            $dt->id = $dataSource;
        } else {
            $dt->id = implode(":", $component);
            $dt->dataSource = $dataSource;
        }

        return $dt->dataTable();
    }

    /**
     * @param bool $ajax
     * @param string|null $template
     */
    public function __construct(bool $ajax = true, string $template = null)
    {
        $this->xhr                      = $ajax;
        $this->xhrSearch                = $ajax;
        $this->xhrSort                  = $ajax;
        $this->xhrPagination            = $ajax;
        if ($template) {
            $this->template = $template;
        }
    }

    /**
     * @param string $model
     * @return $this
     */
    public function sourceOrm(string $model) : self
    {
        $this->dataSource               = self::DATA_SOURCE_ORM;
        $this->id                       = $model;

        return $this;
    }

    /**
     * @param string $model
     * @param array $headers
     * @return Api
     */
    public function sourceApi(string $model, array $headers = []) : Api
    {
        $this->dataSource               = self::DATA_SOURCE_API;
        $this->id                       = $model;
        $this->api                      = new Api($this->id, $headers);

        return $this->api;
    }

    /**
     * @param array $data
     * @return $this
     */
    public function sourceArray(array $data) : self
    {
        /**
         * @todo da definire il funzionamento
         */

        $this->dataSource               = self::DATA_SOURCE_ARRAY;
        $this->records                  = $data;

        return $this;
    }

    /**
     * @param DataTableResponse $data
     * @return $this
     */
    public function sourceDataTable(DataTableResponse $data) : self
    {
        $this->dataSource               = self::DATA_SOURCE_TABLE;
        $this->dataTable                = $data;

        return $this;
    }

    /**
     * @param string $query
     * @return $this
     */
    public function sourceSql(string $query) : self
    {
        /**
         * @todo da definire il funzionamento
         */
        $this->dataSource               = self::DATA_SOURCE_SQL;
        $this->id                       = null;

        return $this;
    }

    /**
     * @param array $query
     * @return $this
     */
    public function sourceNoSql(array $query) : self
    {
        /**
         * @todo da definire il funzionamento
         */
        $this->dataSource               = self::DATA_SOURCE_NOSQL;
        $this->id                       = null;

        return $this;
    }

    /**
     * @return DataHtml
     * @throws Exception
     */
    public function display() : DataHtml
    {
        $this->dataTable                = $this->dataTable();

        $this->pages                    = ceil($this->dataTable->recordsFiltered / $this->length);
        $this->page                     = floor($this->start / $this->length) + 1;

        return new DataHtml($this->toArray());
    }

    /**
     * @param string $id
     * @return Button
     */
    public function buttonHeader(string $id) : Button
    {
        return $this->button($this->buttonsHeader, $id);
    }

    /**
     * @param string $id
     * @return Button
     */
    public function buttonRecord(string $id) : Button
    {
        return $this->button($this->buttonsRecord, $id);
    }

    /**
     * @param string $id
     * @return Button
     */
    public function buttonFooter(string $id) : Button
    {
        return $this->button($this->buttonsFooter, $id);
    }

    /**
     * @param string $id
     * @param array $params
     * @return DataTableColumn|null
     */
    public function column(string $id, array $params = []) : ?DataTableColumn
    {
        return $this->columns[$id] ?? ($this->columns[$id] = DataTableColumn::create($id, $params));
    }

    /**
     * @param string $title
     * @param bool $translate
     * @param bool $encode
     * @return $this
     * @throws Exception
     */
    public function title(string $title, bool $translate = true, bool $encode = false) : self
    {
        $this->title = $title;
        return $this->setPropertyHtml($this->title, $translate, $encode);
    }

    /**
     * @param string $description
     * @param bool $translate
     * @param bool $encode
     * @return $this
     * @throws Exception
     */
    public function description(string $description, bool $translate = true, bool $encode = false) : self
    {
        $this->description = $description;
        return $this->setPropertyHtml($this->description, $translate, $encode);
    }

    /**
     * @param int $record_per_page
     * @return $this
     */
    public function limit(int $record_per_page) : self
    {
        $this->length = $record_per_page;

        return $this;
    }

    /**
     * @param string $key
     * @param bool|null $modal
     * @return $this
     */
    public function record(string $key = self::RECORD_KEY, bool $modal = null) : self
    {
        $this->record_key       = $key;

        foreach (static::BUTTONS as $location => $buttons) {
            foreach ($buttons as $id => $params) {
                $button = $this->button($this->{"buttons" . ucfirst($location)}, $id, $params);
                if ($modal === true) {
                    $button->ajaxModal();
                } elseif ($modal === false) {
                    $button->ajaxNone();
                }
            }
        }

        return $this;
    }

    /**
     * @param string $column_name
     * @param string $dir
     * @return $this
     */
    public function sortBy(string $column_name, string $dir = self::DEFAULT_SORT_DIR) : self
    {
        $this->default_sort     = strtolower($column_name);
        $this->default_sort_dir = $dir;

        return $this;
    }

    /**
     * @param bool $ajax
     * @return $this
     */
    public function ajaxSearch(bool $ajax) : self
    {
        $this->xhrSearch = $ajax;

        return $this;
    }
    /**
     * @param bool $ajax
     * @return $this
     */
    public function ajaxSort(bool $ajax) : self
    {
        $this->xhrSort = $ajax;

        return $this;
    }
    /**
     * @param bool $ajax
     * @return $this
     */
    public function ajaxPagination(bool $ajax) : self
    {
        $this->xhrPagination = $ajax;

        return $this;
    }
    /**
     * @param array $actions
     * @param string $id
     * @param array $params
     * @return Button|null
     */
    private function button(array &$actions, string $id, array $params = []) : ?Button
    {
        return $actions[$id] ?? ($actions[$id] = Button::create($id, $params));
    }

    /**
     * @param string $property
     * @param bool $translate
     * @param bool $encode
     * @return $this
     * @throws Exception
     */
    private function setPropertyHtml(string &$property, bool $translate = true, bool $encode = false) : self
    {
        if ($translate) {
            $property               = Translator::getWordByCode($property);
        }
        if ($encode) {
            $property               = htmlspecialchars($property);
        }
        return $this;
    }

    /**
     * @return string
     */
    protected function getName() : string
    {
        return static::class . ":" . $this->id . ":" . $this->dataSource;
    }

    /**
     * @return DataTableResponse
     */
    protected function dataTable() : DataTableResponse
    {
        $this->request                          = Kernel::$Page->getRequest();

        $request                                = (object)$this->request;
        $this->draw                             = $request->draw ?? 1;
        if (isset($request->key)) {
            $this->record_key                   = $request->key;
        }
        if (isset($request->search)) {
            $this->search                       = $request->search["value"] ?? $request->search;
        }

        if (!empty($request->sort)) {
            $this->sort                         = (array) $request->sort;
        }

        $this->length                           = (int)($request->length ?? $this->length ?? self::RECORD_LIMIT);
        if ($this->length < 1) {
            $this->start                        = self::RECORD_LIMIT;
        }
        $this->start                            = (int)($request->start ?? ($this->length * (((int)($request->page ?? 1)) - 1)));
        if ($this->start < 0) {
            $this->start                        = 0;
        }

        return $this->dataSource();
    }

    /**
     * @return DataTableResponse
     */
    private function dataSource() : dataTableResponse
    {
        $dataTable                      = $this->{$this->dataSource . "Source"}();
        if (empty($dataTable->columns)) {
            $dataTable->columns         = $dataTable->columns();
        }
        if (empty($dataTable->keys) && !empty($this->record_key)) {
            $dataTable->keys            = $dataTable->getColumn($this->record_key);
        }
        return $dataTable;
    }

    /**
     * @return DataTableResponse
     * @throws Exception
     */
    private function ormSource() : DataTableResponse
    {
        $db                                             = new Model($this->id);

        $schema                                         = $db->schema(static::CONVERT_BY_TYPE);

        foreach ($schema->properties as $key => $params) {
            $this->column($key, $params);
        }

        $where = null;
        if ($this->search) {
            $where = ['$or' => array_fill_keys($schema->columns, ['$regex' => "*" . $this->search . "*"])];
        }

        $sort = null;
        foreach ($this->sort as $i => $dir) {
            if (isset($schema->columns[$i])) {
                $sort[$schema->prototype[$schema->columns[$i]]] = $dir;
            }
        }

        if (empty($sort) && !empty($this->default_sort)) {
            $sort[$this->default_sort] = $this->default_sort_dir;
        }

        return $db->read($where, $sort, $this->length, $this->start)
            ->toDataTable(function (OrmResults $results, DataTableResponse $dataTableResponse) use ($schema, $db) {
                $dataTableResponse->draw                = $this->draw + 1;
                $dataTableResponse->columns             = $schema->columns;
                $dataTableResponse->properties          = $schema->properties;
                if ($dataTableResponse->recordsFiltered) {
                    if (!empty($this->search)) {
                        $dataTableResponse->recordsTotal = $db->count();
                    }

                    if (!empty($this->record_key)) {
                        $dataTableResponse->keys        = $results->keys($this->record_key);
                    }
                }
            });
    }

    /**
     * @return DataTableResponse
     * @throws Exception
     */
    protected function apiSource() : DataTableResponse
    {
        $response                   = $this->api->post($this->apiRequestParams());
        if (!$response instanceof DataTableResponse) {
            throw new Exception($this->id . ": sourceApi require DataTableResponse", 501);
        }

        return $response;
    }

    /**
     * @return DataTableResponse
     */
    private function arraySource() : DataTableResponse
    {
        $dataTable                  = new DataTableResponse($this->records, $this->record_key);
        if (isset($this->search)) {
            $dataTable->search($this->search);
        }

        if (empty($this->sort) && !empty($this->default_sort)) {
            $this->sort[array_search($this->default_sort, array_map('strtolower', $dataTable->columns()))] = $this->default_sort_dir;
        }

        foreach ($this->sort as $i => $dir) {
            $dataTable->sort($i, $dir);
            break;
        }
        $dataTable->splice($this->start, $this->length);

        return $dataTable;
    }

    private function sqlSource() : DataTableResponse
    {
    }

    private function nosqlSource() : DataTableResponse
    {
    }

    /**
     * @return array
     */
    protected function apiRequestParams() : array
    {
        return [
            "search"    => ["value" => $this->search],
            "order"     => $this->order(),
            "start"     => $this->start,
            "length"    => $this->length,
            "key"       => $this->record_key,
            "discover"  => $this->xhr
        ];
    }

    /**
     * @return array
     */
    private function order() : array
    {
        $order = [];
        foreach ($this->sort as $column => $dir) {
            $order[] = ["column" => $column, "dir" => $dir];
        }

        if (empty($order) && !empty($this->default_sort)) {
            $order[$this->default_sort] = $this->default_sort_dir;
        }

        return $order;
    }

    /**
     * @return array
     * @throws Exception
     */
    private function toArray() : array
    {
        return [
            "html"              => $this->html(),
            "title"             => $this->title,
            "description"       => $this->description,
            "css"               => static::CSS,
            "style"             => $this->style,
            "js"                => static::JS,
            "js_embed"          => $this->js_embed
        ];
    }

    /**
     * @param string $html
     * @return string
     */
    private function setClass(string $html) : string
    {
        return (empty(static::TEMPLATE_CLASS)
            ? $html
            : str_replace(
                array_keys(static::TEMPLATE_CLASS),
                array_values(static::TEMPLATE_CLASS),
                $html
            )
        );
    }

    /**
     * @return string|null
     */
    private function xhrClass() : ?string
    {
        return ($this->xhr
            ? " " . self::TC_XHR
            : null
        );
    }

    /**
     * @return string
     * @throws Exception
     */
    protected function html() : string
    {
        return $this->setClass('<div id="' . $this->getName() . '" class="dt-component' . $this->xhrClass() . '">' .
            str_replace(
                [
                    "[TITLE]",
                    "[DESCRIPTION]",
                    "[ERROR]",
                    "[ACTION_HEADER]",
                    "[LENGTH]",
                    "[SEARCH]",
                    "[THEAD]",
                    "[TBODY]",
                    "[TFOOT]",
                    "[PAGINATE]",
                    "[PAGINATE_INFO]",
                    "[FOOTER]",
                    "[ACTION_FOOTER]",
                ],
                [
                    $this->parseTitle(),
                    $this->parseDescription(),
                    $this->error(),
                    $this->actions($this->buttonsHeader),
                    $this->tableLength(),
                    $this->tableSearch(),
                    $this->tableHead(),
                    $this->tableBody(),
                    $this->tableFoot(),
                    $this->tablePaginate(),
                    $this->tablePaginateInfo(),
                    $this->footer(),
                    $this->actions($this->buttonsFooter),
                ],
                $this->template
            ) . $this->jsTpl() . '</div>');
    }

    /**
     * @return string|null
     */
    private function jsTpl() : ?string
    {
        $tpl = null;
        foreach ($this->buttonsRecord as $button) {
            $tpl .= $button->displayTpl($this->xhr);
        }

        return $this->js_tpl . (
            $tpl
            ? '<script class="dt-btn" type="text/x-template">' . $tpl . '</script>'
            : null
        );
    }

    /**
     * @return string|null
     */
    private function parseTitle() : ?string
    {
        return ($this->title
            ? '<h3 class="dt-title">' . $this->title . '</h3>'
            : null
        );
    }

    /**
     * @return string|null
     */
    private function parseDescription() : ?string
    {
        return ($this->description
            ? '<p class="dt-description">' . $this->description . '</p>'
            : null
        );
    }

    /**
     * @return string|null
     */
    private function error() : ?string
    {
        return '<div class="' . self::TC_ERROR . '">' . $this->error . '</div>';
    }

    /**
     * @param Button[] $buttons
     * @return string|null
     */
    private function actions(array $buttons) : ?string
    {
        $actions = $this->buttons($buttons);
        return ($actions
            ? '<div class="dt-action">' . $actions . '</div>'
            : null
        );
    }

    private function footer() : ?string
    {
        return null;
    }

    /**
     * @return string|null
     * @throws Exception
     */
    private function tableLength() : ?string
    {
        if ($this->displayTableLength && !empty($this->lengths)) {
            if (!in_array($this->length, $this->lengths)) {
                $this->lengths[] = $this->length;
                sort($this->lengths);
            }
            $lenths = null;
            foreach ($this->lengths as $lenth) {
                $lenths .= '<option value="' . $lenth . '"' . ($this->length == $lenth ? " " . self::TC_SELECTED : null) . '>' . $lenth . '</option>';
            }

            return '<div class="dataTable-dropdown"><label>' . Translator::getWordByCode("Show") . ' <select name="length">' . $lenths . '</select> ' . Translator::getWordByCode("entries") . '</label></div>';
        }

        return null;
    }

    /**
     * @return string|null
     * @throws Exception
     */
    private function tableSearch() : ?string
    {
        return ($this->displayTableSearch
            ? '<div class="dataTable-search">' . Translator::getWordByCode("Search") . '<input type="search" name="search" value="' . htmlspecialchars($this->search) . '"/></div>'
            : null
        );
    }

    /**
     * @return string|null
     * @throws Exception
     */
    private function tableHead() : ?string
    {
        return ($this->displayTableHead && !empty($this->dataTable->columns)
            ?   '<thead>' .
                    $this->tableColumns() .
                '</thead>'
            : null
        );
    }

    private function parseRecordAttr() : ?string
    {
        $record = null;
        if ($this->xhr) {
            if ($this->record_key) {
                $record .= ' data-key="' . $this->record_key . '"';
            }
        }

        return $record;
    }

    /**
     * @return string
     * @throws Exception
     */
    private function tableBody() : string
    {
        return '<tbody' . $this->parseRecordAttr() . '>'
            . (
                $this->dataTable->recordsFiltered && $this->start <= $this->dataTable->recordsFiltered
                ? $this->tableRows()
                : '<tr><td class="dt-empty" colspan="' . count($this->dataTable->columns ?? []) . '">' . Translator::getWordByCode("No matching records found") . '</td></tr>'
            )
            . '</tbody>';
    }

    /**
     * @return string|null
     * @throws Exception
     */
    private function tableFoot() : ?string
    {
        return ($this->displayTableFoot && !empty($this->dataTable->columns)
            ?   '<tfoot>' .
                    $this->tableColumns() .
                '</tfoot>'
            : null
        );
    }

    /**
     * @return string|null
     * @throws Exception
     */
    private function tablePaginate() : ?string
    {
        if ($this->displayTablePaginate) {
            $pages = null;

            $page_prev = $this->page - 1;
            $page_next = $this->page + 1;

            $previous = '<li>' . (
                $this->page <= 1 || $this->page > $this->pages
                ? '<span class="' . self::TC_PREV . '">' . Translator::getWordByCode("Previous") . '</span>'
                : '<a href="' . $this->getUrl(self::TC_PAGE, $page_prev) . '" class="' . self::TC_PREV . '">' . Translator::getWordByCode("Previous") . '</a>'
            ) . '</li>';

            $next = '<li>' . (
                $this->page < 1 || $this->page >= $this->pages
                ? '<span class="' . self::TC_NEXT . '">' . Translator::getWordByCode("Next") . '</span>'
                : '<a href="' . $this->getUrl(self::TC_PAGE, $page_next) . '" class="' . self::TC_NEXT . '">' . Translator::getWordByCode("Next") . '</a>'
            ) . '</li>';

            for ($i = 1; $i <= $this->pages; $i++) {
                $pages .= '<li>' . (
                    $i == $this->page
                    ? '<span class="' . self::TC_PAGE . '">' . $i . '</span>'
                    : '<a href="' . $this->getUrl(self::TC_PAGE, $i) . '" class="' . self::TC_PAGE . '' . ($i == $this->page ? " " . self::TC_CURRENT : null) . '">' . $i . '</a>'
                ) . '</li>';
            }

            return '<nav class="dataTable-pagination"><ul>' . $previous . $pages . $next . '</ul></nav>';
        }

        return null;
    }

    /**
     * @return string|null
     * @throws Exception
     */
    private function tablePaginateInfo() : ?string
    {
        if ($this->start > $this->dataTable->recordsFiltered || !$this->dataTable->recordsFiltered) {
            $start = 0;
            $length = 0;
        } else {
            $length = (
                $this->start + $this->length > $this->dataTable->recordsFiltered
                ? $this->dataTable->recordsFiltered
                : $this->start + $this->length
            );
            $start = $this->start + 1;
        }

        $total = (
            $this->dataTable->recordsFiltered != $this->dataTable->recordsTotal
            ? ' (' . Translator::getWordByCode("filtered from") . ' '  . $this->dataTable->recordsTotal . ' ' . Translator::getWordByCode("total entries") . ')'
            : null
        );
        return ($this->displayTablePaginateInfo
            ? '<div class="dataTable-info">' . Translator::getWordByCode("Showing") . ' ' . $start . ' ' . Translator::getWordByCode("to") . ' ' . $length . ' ' . Translator::getWordByCode("of") . ' ' . $this->dataTable->recordsFiltered . ' ' . Translator::getWordByCode("entries") . $total . '</div>'
            : null
        );
    }

    /**
     * @return string
     * @throws Exception
     */
    private function tableColumns() : string
    {
        $columns = null;
        foreach ($this->dataTable->columns as $i => $column) {
            if ($this->column($column)->isHidden()) {
                unset($this->dataTable->columns[$i]);
                continue;
            }

            if ($this->displayTableSort) {
                if (isset($this->sort[$i])) {
                    $dir    = self::DIR[$this->sort[$i]] ?? $this->default_sort_dir;
                    $rdir   = self::RDIR[$dir];
                    $class  = ' class="dataTable-sorter ' . $dir . '"';
                } else {
                    $rdir   = self::DEFAULT_SORT_DIR;
                    $class  = null;
                }

                $columns    .= '<th data-id="' . $column . '"' . $class . '>' . $this->column($column)->displayLabel($this->getUrl(self::TC_SORT, [$i => $rdir])) . '</th>';
            } else {
                $columns    .= '<th><span>' . $column . '</span></th>';
            }
        }

        if (!empty($this->buttonsRecord)) {
            $columns .= '<th class="actions">' . '</th>';
        }

        return '<tr>' . $columns . '</tr>';
    }

    /**
     * @return string|null
     */
    private function tableRows() : ?string
    {
        $rows = null;
        $row = $this->row();
        foreach ($this->dataTable->toArray() as $i => $record) {
            $rows .= '<tr' . $this->setModify($i) . ' class="' . ($i % 2 == 0 ? self::TC_ODD : self::TC_EVEN) . (isset($this->sort[$i]) ? ' ' . self::TC_SORT : null) . '">' . $this->tableRow($record, $row) . $this->tableActions($i) . '</tr>';
        }

        return $rows;
    }

    /**
     * @param int $i
     * @return string|null
     */
    private function tableActions(int $i) : ?string
    {
        $params = [];
        if ($this->record_key && isset($this->dataTable->keys[$i])) {
            $params[$this->record_key] = $this->dataTable->keys[$i];
        }

        $actions = $this->buttons($this->buttonsRecord, $params);
        return ($actions
            ? '<td>' . $actions . '</td>'
            : null
        );
    }

    /**
     * @param Button[] $buttons
     * @param array $params
     * @return string|null
     */
    private function buttons(array $buttons, array $params = []) : ?string
    {
        $actions = null;
        foreach ($buttons as $button) {
            $actions .= $button->display($params, $this->xhr);
        }

        return $actions;
    }

    /**
     * @return stdClass
     */
    private function row() : stdClass
    {
        $fields         = [];
        $tpl            = null;
        $i              = 0;
        foreach ($this->columns as $column) {
            $tpl        .= '<td' . $this->tableRowClass($i, $column->getType("string")) . '>' . $column->displayValue() . '</td>';
            $fields[]   = $column->id();
            $i++;
        }

        if ($tpl) {
            $this->js_tpl .= '<script class="dt-row" type="text/x-template">' . $tpl . '</script>';
        }

        return (object) [
            "tpl"       => $tpl,
            "fields"    => $fields
        ];
    }

    private function tableRow(array $record, stdclass $row) : ?string
    {
        return str_replace(
            $row->fields,
            array_values($record),
            $row->tpl
        );
    }

    /**
     * @param int $i
     * @param string|null $type
     * @return string|null
     */
    private function tableRowClass(int $i, string $type = null) : ?string
    {
        $class = trim($type . (isset($this->sort[$i]) ? ' ' . self::TC_SORT : null));

        return ($class
            ? ' class="' . $class . '"'
            : null
        );
    }

    /**
     * @param int $i
     * @return string|null
     */
    private function setModify(int $i) : ?string
    {
        return ($this->record_key && !empty($this->dataTable->keys[$i])
            ? ' data-id="' . $this->dataTable->keys[$i] . '"'
            : null
        );
    }


    /**
     * @param string $name
     * @param $value
     * @return string
     */
    private function getUrl(string $name, $value) : string
    {
        $request        = $this->request;
        $request[$name] = $value;

        return "?" . http_build_query(array_filter($request));
    }
}
