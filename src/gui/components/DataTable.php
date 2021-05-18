<?php
namespace phpformsframework\libs\gui\components;

use Exception;
use phpformsframework\libs\dto\DataTableResponse;
use phpformsframework\libs\international\Translator;
use phpformsframework\libs\Kernel;
use phpformsframework\libs\storage\dto\OrmResults;
use phpformsframework\libs\storage\Model;

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


    private const RECORD_LIMIT          = 25;

    protected const TEMPLATE_CLASS = [
        "wrapper"       => self::BUCKET . "-wrapper",
        "head"          => self::BUCKET . "-head",
        "table"         => self::BUCKET . "-table",
        "foot"          => self::BUCKET . "-foot",
        "title"         => self::BUCKET . "-title",
        "description"   => self::BUCKET . "-description",
        "actions"       => self::BUCKET . "-actions",
        "length"        => self::BUCKET . "-length",
        "search"        => self::BUCKET . "-search",
        "empty"         => self::BUCKET . "-empty",
        "paginate"      => self::BUCKET . "-paginate",
        "info"          => self::BUCKET . "-info",
    ];


    protected const CSS = [
        "https://db2.creo.it/DataTables-1.10.21/css/datatables.css"
    ];

    protected const JS = [
        "https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js",
        "https://cdn.datatables.net/1.10.24/js/jquery.dataTables.js"
    ];

    public $template                    = ' [TITLE]
                                            [DESCRIPTION]
                                            [ERROR]
                                            [ACTIONS]
                                            <div class="' . self::TEMPLATE_CLASS["head"] . '">
                                                [LENGTH]
                                                [SEARCH]
                                            </div>
                                            [PAGINATE_INFO]
                                            [TABLE]
                                            <div class="' . self::TEMPLATE_CLASS["foot"] . '">
                                                [PAGINATE_INFO]
                                                [PAGINATE]
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

    public $useDataTablePlugin          = false;
    /**
     * @var OrmResults
     */
    public $dataTable                   = null;

    private $id                         = null;
    /**
     * @var Model
     */
    private $db                         = null;
    private $dtd                        = null;

    private $page                       = null;
    private $start                      = null;
    private $length                     = null;
    private $records                    = null;
    private $records_total              = null;
    private $columns                    = null;
    private $pages                      = null;
    private $search                     = null;
    private $sort                       = null;

    private $isXhr                      = false;
    private $query                      = null;
    private $draw                       = null;

    public static function xhr(string $model) : DataTableResponse
    {
        $dt = new static($model);

        return $dt->read()->toDataTableResponse($dt->draw, $dt->records_total);
    }

    /**
     * DataTable constructor.
     * @param string $model
     */
    public function __construct(string $model)
    {
        $this->id                       = self::BUCKET . DIRECTORY_SEPARATOR . $model;

        $this->isXhr                    = Kernel::$Page->isXhr;
        $this->query                    = Kernel::$Page->getRequest();

        $this->db                       = new Model($model);
        $this->dtd                      = $this->db->dtdStore();
        $this->columns                  = $this->db->dtdRaw();

        $request                        = (object) $this->query;
        $this->draw                     = $request->draw    ?? 1;

        if (isset($request->search)) {
            $this->search = $request->search["value"] ?? $request->search;
        }

        $this->sort                     = (array) ($request->sort    ?? []);

        $this->length                   = (int) ($request->length  ?? self::RECORD_LIMIT);
        if ($this->length < 1) {
            $this->start                = self::RECORD_LIMIT;
        }
        $this->start                    = (int) ($request->start ?? ($this->length * (((int)($request->page ?? 1)) - 1)));
        if ($this->start < 0) {
            $this->start                = 0;
        }
    }

    private function read() : OrmResults
    {
        $where                          = null;
        $sort                           = null;

        if ($this->search) {
            $where                      =  ['$or' =>  array_fill_keys($this->columns, ['$regex' => "*" . $this->search . "*"])];
        }

        foreach ($this->sort as $i => $dir) {
            $sort[$this->columns[$i]]   = $dir;
        }

        $records                        = $this->db->read($where, $sort, $this->length, $this->start);

        $this->records                  = $records->countTotal();
        $this->records_total            = (
            empty($this->search)
            ? $this->records
            : $this->db->count()
        );

        return $records;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function display() : string
    {
        $this->dataTable                = $this->read();

        $this->pages                    = ceil($this->records / $this->length);
        $this->page                     = floor($this->start / $this->length) + 1;

        return $this->draw();
    }

    private function tClass() : object
    {
        return (object) static::TEMPLATE_CLASS;
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
    private function draw() : string
    {
        return ($this->useDataTablePlugin
            ? $this->pluginDataTable()
            : $this->css() .
                '<div id="' . $this->id . '" class="' . $this->tClass()->wrapper . $this->xhrClass() . '">' .
                str_replace(
                    [
                        "[TABLE]",
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
                        $this->table(),
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
                ) . '</div>' . $this->js()
        );
    }

    private function table() : string
    {
        return '<table class="' . $this->tClass()->table . '">
                [THEAD]
                [TBODY]
                [TFOOT]
            </table>';
    }

    /**
     * @return string|null
     */
    private function title() : ?string
    {
        return ($this->title
            ? '<h3 class="' . $this->tClass()->title . '">' . $this->title . '</h3>'
            : null
        );
    }

    /**
     * @return string|null
     */
    private function description() : ?string
    {
        return ($this->title
            ? '<p class="' . $this->tClass()->description . '">' . $this->description . '</p>'
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
            ? '<div class="' . $this->tClass()->actions . '">' . $actions . '</div>'
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

            return '<span class="' . $this->tClass()->length . '"><label>' . Translator::getWordByCode("Show") . ' <select name="length">' . $lenths . '</select> ' . Translator::getWordByCode("entries") . '</label></span>';
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
            ? '<span class="' . $this->tClass()->search . '">' . Translator::getWordByCode("Search") . '<input type="search" name="search" value="' . htmlspecialchars($this->search) . '"/></span>'
            : null
        );
    }

    /**
     * @return string|null
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
        return '<tbody>'
            . (
                $this->records && $this->start <= $this->records
                ? $this->tableRows()
                : '<tr><td class="' . $this->tClass()->empty . '" colspan="' . count($this->columns) . '">' . Translator::getWordByCode("No matching records found") . '</td></tr>'
            )
            . '</tbody>';
    }

    /**
     * @return string|null
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
        if ($this->displayTablePaginate && $this->pages  >= 1) {
            $pages = null;

            $page_prev = $this->page - 1;
            $page_next = $this->page + 1;

            $previous = (
                $this->page <= 1 || $this->page > $this->pages
                ? '<span class="' . self::TC_PREV . '">' . Translator::getWordByCode("Previous") . '</span>'
                : '<a href="' . $this->getUrl(self::TC_PAGE, $page_prev) . '" class="' . self::TC_PREV . '">' . Translator::getWordByCode("Previous") . '</a>'
            );

            $next = (
                $this->page < 1 || $this->page >= $this->pages
                ? '<span class="' . self::TC_NEXT . '">' . Translator::getWordByCode("Next") . '</span>'
                : '<a href="' . $this->getUrl(self::TC_PAGE, $page_next) . '" class="' . self::TC_NEXT . '">' . Translator::getWordByCode("Next") . '</a>'
            );

            for ($i = 1; $i <= $this->pages; $i++) {
                $pages .= (
                    $i == $this->page
                    ? '<span class="' . self::TC_PAGE . '">' . $i . '</span>'
                    : '<a href="' . $this->getUrl(self::TC_PAGE, $i) . '" class="' . self::TC_PAGE . '' . ($i == $this->page ? " " . self::TC_CURRENT : null) . '">' . $i . '</a>'
                );
            }

            return '<span class="' . $this->tClass()->paginate . '">' . $previous . $pages . $next . '</span>';
        }

        return null;
    }

    /**
     * @return string|null
     * @throws Exception
     */
    private function tablePaginateInfo() : ?string
    {
        if ($this->start > $this->records) {
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
        return ($this->displayTablePaginateInfo && $this->records
            ? '<span class="' . $this->tClass()->info . '">' . Translator::getWordByCode("Showing") . ' ' . $start . ' ' . Translator::getWordByCode("to") . ' ' . $length . ' ' . Translator::getWordByCode("of") . ' ' . $this->records . ' ' . Translator::getWordByCode("entries") . $total . '</span>'
            : null
        );
    }

    /**
     * @return string
     */
    private function tableColumns() : string
    {
        $columns = null;
        foreach ($this->columns as $i => $column) {
            if ($this->displayTableSort) {
                $class  = (isset($this->sort[$i]) ? ' class="' . self::TC_SORT . ' ' . $this->sort[$i] . '"' : null);
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
            $rows .= '<tr class="' . ($i % 2 == 0 ? self::TC_ODD : self::TC_EVEN) . (isset($this->sort[$i]) ? ' ' . self::TC_SORT : null) . '">' . $this->tableRow($record) . '</tr>';
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

    /**
     * @return string
     */
    private function css() : string
    {
        return '<style>
            .dt-head, .dt-foot {
                display: flex;
                justify-content: space-between;
            }  
           
            .dt-table {
                width: 100%;
            }
            .dt-table .sort.desc::after {
                content : " \2193";
            }
            .dt-table .sort.asc::after {
                content : " \2191";
            }
            .dt-table tr.odd {
            
            }
            .dt-table tr.even {
                opacity: 0.7;
            }
            .dt-table td.sort {
                opacity: 0.7;
            }
            .dt-wrapper INPUT, .dt-wrapper SELECT, .dt-wrapper BUTTON, .dt-wrapper A, .dt-wrapper SPAN {
                padding: 0.3em;
            }
            .dt-empty {
                text-align: center;
            }
        </style>';
    }

    /**
     * @return string
     */
    private function js() : string
    {
        return '
        <script defer type="application/javascript" src="/assets/js/cm.dataTable.js?' . 444 . '"></script>
        ';
    }

    /**
     * @return string
     */
    private function pluginDataTable() : string
    {
        $order = [];
        foreach ($this->sort ?? [] as $sort => $dir) {
            $order[] = [$sort, $dir];
        }

        $search = (
            $this->search
            ? ["search" => $this->search]
            : []
        );


        foreach ($this->sort ?? [] as $sort => $dir) {
            $order[] = [$sort, $dir];
        }

        $columns = null;
        $columns_json = [];
        foreach ($this->columns as $column) {
            $columns                    .= '<th>' . $column . '</th>';
            $columns_json[]["data"]     = $column;
        }

        $rows = null;
        foreach ($this->dataTable->getAllArray() as $record) {
            $rows                       .= '<tr><td>' . implode('</td><td>', $record). '</td></tr>';
        }

        $embed                          = '<table class="dt-table" data-component="' . $this->id . '">
                                            <thead>
                                                <tr>' . $columns . '</tr>
                                            </thead>
                                            <tbody>
                                                ' . $rows . '
                                            </tbody>
                                        </table>';

        foreach (self::JS as $js) {
            $embed                      .= '<script type="text/javascript" src="' . $js . '"></script>';
        }

        foreach (self::CSS as $css) {
            $embed                      .= '<link type="text/css" rel="stylesheet" href="' . $css . '" />';
        }

        $embed                          .= '<script type="text/javascript">
                                            $(".dt-table")
                                            .on("preXhr.dt", function ( e, settings, data ) {                                                
                                                let url = new URL(window.location);
                                                
                                                url.searchParams.set("page", Math.floor( data.start / data.length ) + 1);
                                                url.searchParams.set("length", data.length);

                                                for(let i = 0; i < settings.aLastSort.length; i++) {
                                                    url.searchParams.delete("sort[" + settings.aLastSort[i].col + "]");
                                                }
                                                for(let i = 0; i < data.order.length; i++) {
                                                    url.searchParams.set("sort[" + data.order[i].column + "]", data.order[i].dir);
                                                }
                                                
                                                if(data.search.value.length) {
                                                    url.searchParams.set("search", data.search.value);
                                                } else {
                                                    url.searchParams.delete("search");
                                                }
                                                
                                                window.history.pushState({}, "", url);
                                                
                                                url.searchParams.set("component", $(this).data("component"));
                                                
                                                settings.ajax.url = url.toString();
                                            })
                                            .on("xhr.dt", function ( e, settings, json, xhr ) {
                                                if ( xhr.status === 204 || xhr.responseText === "null" ) {
                                                    json = {};
                                                }
                                            })
                                            .DataTable({
                                                processing: true,
                                                serverSide: true,
                                                search: ' . json_encode($search) . ',
                                                order: ' . json_encode($order) . ',
                                                displayStart: ' . $this->start . ',
                                                pageLength: ' . $this->length . ',
                                                ajax: {
                                                    url: window.location.href,
                                                    type: "POST",
                                                    dataType : "json"
                                                },
                                                columns: ' . json_encode($columns_json). ',
                                                deferLoading: ' . $this->records . '
                                            });
                                        </script>';

        return $embed;
    }
}