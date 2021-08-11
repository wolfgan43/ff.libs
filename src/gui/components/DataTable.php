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
namespace phpformsframework\libs\gui\components;

use phpformsframework\libs\dto\DataHtml;
use phpformsframework\libs\dto\DataTableResponse;
use phpformsframework\libs\international\Translator;
use phpformsframework\libs\Kernel;
use phpformsframework\libs\storage\dto\OrmResults;
use phpformsframework\libs\storage\Model;
use phpformsframework\libs\Exception;

/**
 * Class DataTable
 * @package phpformsframework\libs\gui\components
 */
class DataTable
{
    public const BUCKET                 = "dt";

    /**
     * Token Class
     */
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

    private const DEFAULT_SORT          = "0";
    private const DEFAULT_SORT_DIR      = "asc";

    private const RECORD_LIMIT          = 25;

    protected const TEMPLATE_CLASS      = [];

    protected const CSS                 = [
        "cm.dataTable"
    ];

    protected const JS                  = [
        "cm.dataTable"
    ];


    public $template                    = '[TITLE]
                                            [DESCRIPTION]
                                            [ERROR]
                                            [ACTIONS]
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
                                            </div>';

    public $displayTableHead            = true;
    public $displayTableFoot            = false;

    public $displayTableLength          = true;
    public $displayTableSearch          = true;
    public $displayTableSort            = true;

    public $displayTablePaginate        = true;
    public $displayTablePaginateInfo    = true;

    public $xhr                         = false;

    public $lengths                     = [10, 25, 50];
    public $title                       = null;
    public $description                 = null;
    public $error                       = null;
    public $actions                     = [];

    /**
     * @var OrmResults
     */
    public $dataTable                   = null;

    protected $id                       = null;
    /**
     * @var Model
     */
    private $db                         = null;
    private $dtd                        = null;
    protected $schema                   = null;

    private $page                       = null;
    protected $start                    = null;
    protected $length                   = null;
    protected $records                  = null;
    private $records_total              = null;
    protected $columns                  = null;
    private $pages                      = null;
    protected $search                   = null;
    protected $sort                     = null;

    private $isXhr                      = false;
    private $query                      = null;
    private $draw                       = null;

    protected $style                    = [];
    protected $js_embed                 = null;

    public $record_url                  = null;
    public $record_key                  = null;

    /**
     * @param string $model
     * @return DataTableResponse
     * @throws Exception
     */
    public static function xhr(string $model): DataTableResponse
    {
        $dt = new static($model);

        return $dt->read()->toDataTableResponse($dt->draw, $dt->records_total);
    }

    /**
     * DataTable constructor.
     * @param string $model
     * @param string|null $view
     */
    public function __construct(string $model, string $view = null)
    {
        $this->isXhr                    = Kernel::$Page->isXhr;
        $this->query                    = Kernel::$Page->getRequest();

        $this->db                       = new Model($model, $view);
        $this->dtd                      = $this->db->dtdStore();
        $this->schema                   = $this->db->schema();
        $this->id                       = self::BUCKET . DIRECTORY_SEPARATOR . $this->schema->id;

        $request                        = (object)$this->query;
        $this->draw                     = $request->draw ?? 1;

        if (isset($request->search)) {
            $this->search               = $request->search["value"] ?? $request->search;
        }

        $this->sort                     = (array)($request->sort ?? []);

        $this->length                   = (int)($request->length ?? self::RECORD_LIMIT);
        if ($this->length < 1) {
            $this->start                = self::RECORD_LIMIT;
        }
        $this->start                    = (int)($request->start ?? ($this->length * (((int)($request->page ?? 1)) - 1)));
        if ($this->start < 0) {
            $this->start = 0;
        }
    }

    /**
     * @return OrmResults
     * @throws Exception
     */
    private function read(): OrmResults
    {
        $where = null;
        $sort = null;

        if ($this->search) {
            $where = ['$or' => array_fill_keys($this->schema->columns, ['$regex' => "*" . $this->search . "*"])];
        }

        foreach ($this->sort as $i => $dir) {
            if (isset($this->schema->columns[$i])) {
                $sort[$this->schema->columns[$i]] = $dir;
            }
        }

        if (!$sort) {
            $this->sort[self::DEFAULT_SORT] = self::DEFAULT_SORT_DIR;
            $sort[$this->schema->columns[self::DEFAULT_SORT]] = self::DEFAULT_SORT_DIR;
        }
        $records = $this->db->read($where, $sort, $this->length, $this->start);

        $this->records = $records->countTotal();
        $this->records_total = (
            empty($this->search)
            ? $this->records
            : $this->db->count()
        );

        return $records;
    }

    /**
     * @return DataHtml
     * @throws Exception
     */
    public function display() : DataHtml
    {
        $this->dataTable                = $this->read();

        $this->pages                    = ceil($this->records / $this->length);
        $this->page                     = floor($this->start / $this->length) + 1;

        return new DataHtml($this->toArray());
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
        return $this->setClass('<div id="' . $this->id . '" class="dt-wrapper' . $this->xhrClass() . '">' .
            str_replace(
                [
                    "[TITLE]",
                    "[DESCRIPTION]",
                    "[ERROR]",
                    "[ACTIONS]",
                    "[LENGTH]",
                    "[SEARCH]",
                    "[THEAD]",
                    "[TBODY]",
                    "[TFOOT]",
                    "[PAGINATE]",
                    "[PAGINATE_INFO]"
                ],
                [
                    $this->title(),
                    $this->description(),
                    $this->error(),
                    $this->actions(),
                    $this->tableLength(),
                    $this->tableSearch(),
                    $this->tableHead(),
                    $this->tableBody(),
                    $this->tableFoot(),
                    $this->tablePaginate(),
                    $this->tablePaginateInfo()
                ],
                $this->template
            ) . '</div>');
    }

    /**
     * @return string|null
     */
    private function title() : ?string
    {
        return ($this->title
            ? '<h3 class="dt-title">' . $this->title . '</h3>'
            : null
        );
    }

    /**
     * @return string|null
     */
    private function description() : ?string
    {
        return ($this->title
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
     * @return string|null
     */
    private function actions() : ?string
    {
        $actions = null;
        foreach ($this->actions as $key => $action) {
            $actions .= '<a href="' . $action["url"] . '" class="dbt-' . $key . ($action["xhr"] ? " " . self::TC_XHR : "") . '">' . $action["label"] . '</a>';
        }

        return ($actions
            ? '<div class="dt-actions">' . $actions . '</div>'
            : null
        );
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
        return ($this->displayTableHead
            ?   '<thead>' .
                    $this->tableColumns() .
                '</thead>'
            : null
        );
    }

    /**
     * @return string
     * @throws Exception
     */
    private function tableBody() : string
    {
        return '<tbody' . ($this->xhr && $this->record_url ? ' data-url="' . $this->record_url . '"' : null) . '>'
            . (
                $this->records && $this->start <= $this->records
                ? $this->tableRows()
                : '<tr><td class="dt-empty" colspan="' . count($this->schema->columns) . '">' . Translator::getWordByCode("No matching records found") . '</td></tr>'
            )
            . '</tbody>';
    }

    /**
     * @return string|null
     * @throws Exception
     */
    private function tableFoot() : ?string
    {
        return ($this->displayTableFoot
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
        if ($this->start > $this->records || !$this->records) {
            $start = 0;
            $length = 0;
        } else {
            $length = (
                $this->start + $this->length > $this->records
                ? $this->records
                : $this->start + $this->length
            );
            $start = $this->start + 1;
        }

        $total = (
            $this->records != $this->records_total
            ? ' (' . Translator::getWordByCode("filtered from") . ' '  . $this->records_total . ' ' . Translator::getWordByCode("total entries") . ')'
            : null
        );
        return ($this->displayTablePaginateInfo
            ? '<div class="dataTable-info">' . Translator::getWordByCode("Showing") . ' ' . $start . ' ' . Translator::getWordByCode("to") . ' ' . $length . ' ' . Translator::getWordByCode("of") . ' ' . $this->records . ' ' . Translator::getWordByCode("entries") . $total . '</div>'
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
        foreach ($this->schema->columns as $i => $column) {
            if ($this->displayTableSort) {
                $class  = (isset($this->sort[$i]) ? ' class="dataTable-sorter ' . $this->sort[$i] . '"' : null);
                $dir    = self::RDIR[$this->sort[$i] ?? null];

                $columns .= '<th data-id="' . $column . '"' . $class . '><a href="' . $this->getUrl(self::TC_SORT, [$i => $dir]) . '">' . Translator::getWordByCode($column) . '</a></th>';
            } else {
                $columns .= '<th>' . $column . '</th>';
            }
        }

        return '<tr>' . $columns . '</tr>';
    }

    /**
     * @return string|null
     */
    private function tableRows() : ?string
    {
        $rows = null;
        foreach ($this->dataTable->getAllArray() as $i => $record) {
            $rows .= '<tr' . $this->setModify($i) . ' class="' . ($i % 2 == 0 ? self::TC_ODD : self::TC_EVEN) . (isset($this->sort[$i]) ? ' ' . self::TC_SORT : null) . '">' . $this->tableRow($record) . '</tr>';
        }

        return $rows;
    }

    /**
     * @param array $record
     * @return string|null
     */
    private function tableRow(array $record) : ?string
    {
        $row = null;
        $i = 0;
        foreach ($record as $field => $value) {
            $row .= '<td' . $this->tableRowClass($field, $i) . '>' . $value . '</td>';
            $i++;
        }

        return $row;
    }

    /**
     * @param string $field
     * @param int $i
     * @return string|null
     */
    private function tableRowClass(string $field, int $i) : ?string
    {
        $class = trim(($this->dtd->$field != "string" ? $this->dtd->$field : null) . (isset($this->sort[$i]) ? ' ' . self::TC_SORT : null));

        return ($class
            ? ' class="' . $class . '"'
            : null
        );
    }

    private function setModify(int $index) : ?string
    {
        return ($this->record_url
            ? ' data-key="' . $this->dataTable->key($index) . '"'
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
        $query          = $this->query;
        $query[$name]   = $value;

        return "?" . http_build_query(array_filter($query));
    }
}
