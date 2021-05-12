<?php
namespace phpformsframework\libs\gui\components;

use Exception;
use phpformsframework\libs\international\Translator;
use phpformsframework\libs\Kernel;
use phpformsframework\libs\Response;
use phpformsframework\libs\storage\dto\OrmResults;
use phpformsframework\libs\storage\Model;

/**
 * Class DataTable
 * @package phpformsframework\libs\gui\components
 */
class DataTable
{
    private const SORT_DIR              = [
                                            'asc'   => '&#9650;',
                                            'desc'  => '&#9660;'
                                        ];
    private const RECORD_LIMIT          = 25;

    protected const CSS = [
        "https://db2.creo.it/DataTables-1.10.21/css/datatables.css"
    ];

    protected const JS = [
        "https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js",
        "https://cdn.datatables.net/1.10.24/js/jquery.dataTables.js"
    ];

    public $class                       = [
                                            "wrapper" => "dt-wrapper",

                                        ];

    public $template                    = ' [TITLE]
                                            [DESCRIPTION]
                                            [ERROR]
                                            [ACTIONS]
                                            <div class="dt-head">
                                                [LENGTH]
                                                [SEARCH]
                                            </div>
                                            [PAGINATE_INFO]
                                            <table class="dt-table">
                                                [THEAD]
                                                [TBODY]
                                                [TFOOT]
                                            </table>
                                            <div class="dt-foot">
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
    private $columns                    = null;
    private $pages                      = null;
    private $search                     = null;
    private $sort                       = null;

    private $isXhr                      = false;
    private $query                      = null;
    private $draw                       = null;


    /**
     * DataTable constructor.
     * @param string $model
     */
    public function __construct(string $model)
    {
        $this->id                       = "dt/" . $model;

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

        $this->sort                     = $request->sort    ?? [];

        $this->length                   = (int) ($request->length  ?? self::RECORD_LIMIT);
        if ($this->length < 1) {
            $this->start                = self::RECORD_LIMIT;
        }
        $this->start                    = (int) ($request->start ?? ($this->length * (((int)$request->page) - 1)));
        if ($this->start < 0) {
            $this->start                = 0;
        }
    }

    /**
     * @return string
     * @throws Exception
     */
    public function display() : string
    {
        $where                          = null;
        $sort                           = null;

        if ($this->search) {
            $where                      =  ['$or' =>  array_fill_keys($this->columns, ['$regex' => "*" . $this->search . "*"])];
        }

        foreach ($this->sort as $i => $dir) {
            $sort[$this->columns[$i]]   = $dir;
        }

        $this->dataTable                = $this->db->read($where, $sort, $this->length, $this->start);
        $this->records                  = $this->dataTable->countTotal();

        $this->pages                    = ceil($this->records / $this->length);
        $this->page                     = floor($this->start / $this->length) + 1;

        return ($this->isXhr
            ? Response::send($this->dataTable->toDataTableResponse($this->draw, !$this->useDataTablePlugin  && !empty($this->search)))
            : $this->draw()
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
                '<form id="' . $this->id . '" class="dt-wrapper' . ($this->xhr ? " cm-xhr" : null) . '">' .
                $this->hiddens() .
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
                ) . '</form>' . $this->js()
        );
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
        return '<div class="cm-error">' . $this->error . '</div>';
    }

    /**
     * @return string|null
     */
    private function actions() : ?string
    {
        $actions = null;
        foreach ($this->actions as $key => $action) {
            $actions .= '<a href="' . $action["url"] . '" class="dbt-' . $key . ($action["xhr"] ? " cm-xhr" : "") . '">' . $action["label"] . '</a>';
        }

        return ($actions
            ? '<div class="dt-buttons">' . $actions . '</div>'
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
                $lenths .= '<option value="' . $lenth . '"' . ($this->length == $lenth ? " selected" : null) . '>' . $lenth . '</option>';
            }

            return '<span class="dt-length"><label>' . Translator::getWordByCode("Show") . ' <select name="length" onchange="cm.dataTable(\'' . $this->id . '\').length();">' . $lenths . '</select> ' . Translator::getWordByCode("entries") . '</label></span>';
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
            ? '<span class="dt-search">' . Translator::getWordByCode("Search") . '<input type="search" name="search" onkeyup="cm.dataTable(\'' . $this->id . '\').search(event);" value="' . htmlspecialchars($this->search) . '"/></span>'
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
                : '<tr><td class="dt-empty" colspan="' . count($this->columns) . '">' . Translator::getWordByCode("No matching records found") . '</td></tr>'
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
        if ($this->displayTablePaginate && $this->pages  > 1) {
            $pages = null;

            $page_prev = $this->page - 1;
            $page_next = $this->page + 1;

            $previous = (
                $this->page <= 1 || $this->page > $this->pages
                ? '<span class="prev">' . Translator::getWordByCode("Previous") . '</span>'
                : '<a href="' . $this->getUrl("page", $page_prev) . '" onclick="cm.dataTable(\'' . $this->id . '\').page(' . $page_prev . ')" class="previous">' . Translator::getWordByCode("Previous") . '</a>'
            );

            $next = (
                $this->page < 1 || $this->page >= $this->pages
                ? '<span class="next">' . Translator::getWordByCode("Next") . '</span>'
                : '<a href="' . $this->getUrl("page", $page_next) . '" onclick="cm.dataTable(\'' . $this->id . '\').page(' . $page_next . ')" class="next">' . Translator::getWordByCode("Next") . '</a>'
            );

            for ($i = 1; $i <= $this->pages; $i++) {
                $pages .= (
                    $i == $this->page
                    ? '<span class="page">' . $i . '</span>'
                    : '<a href="' . $this->getUrl("page", $i) . '" onclick="cm.dataTable(\'' . $this->id . '\').page(' . $i . ')" class="page' . ($i == $this->page ? " current" : null) . '">' . $i . '</a>'
                );
            }

            return '<span class="dt-paginate">' . $previous . $pages . $next . '</span>';
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
            $start = $this->start + 1;
            $length = (
                $this->start + $this->length > $this->records
                ? $this->records
                : $this->start + $this->length
            );
        }

        return ($this->displayTablePaginateInfo && $this->records
            ? '<span class="dt-info">' . Translator::getWordByCode("Showing") . ' ' . $start . ' ' . Translator::getWordByCode("to") . ' ' . $length . ' ' . Translator::getWordByCode("of") . ' ' . $this->records . ' ' . Translator::getWordByCode("entries") . '</span>'
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
                $dir = (
                    isset($this->sort[$i]) && $this->sort[$i] == "asc"
                    ? "desc"
                    : "asc"
                );

                $columns .= '<th><a href="' . $this->getUrl("sort", [$i => $dir]) . '">' . $column . (isset($this->sort[$i]) ? self::SORT_DIR[$this->sort[$i]] : null) . '</a></th>';
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
            $rows .= '<tr class="' . ($i % 2 == 0 ? "odd": "even") . (isset($this->sort[$i]) ? " sorting" : null) . '">' . $this->tableRow($record) . '</tr>';
        }

        return $rows;
    }

    /**
     * @param array $record
     * @return string|null
     */
    private function tableRow(array $record) : ?string
    {
        //return '<td>' . implode('</td><td>', $record). '</td>';

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
        $class = trim(($this->dtd->$field != "string" ? $this->dtd->$field : null) . (isset($this->sort[$i]) ? ' sorting' : null));

        return ($class
            ? ' class="' . $class . '"'
            : null
        );
    }

    /**
     * @return string
     */
    private function hiddens() : string
    {
        $hiddens = null;
        if ($this->displayTablePaginate) {
            $hiddens .= '<input type="hidden" class="dt-page" name="page" value="' . $this->page . '"/>';
        }
        if ($this->displayTableSort) {
            foreach ($this->sort as $i => $dir) {
                $hiddens .= '<input type="hidden" class="dt-page" name="sort[' . $i . ']" value="' . $dir . '"/>';
            }
        }

        return $hiddens;
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
        
            .dt-table tr.odd {
            
            }
            .dt-table tr.even {
                opacity: 0.7;
            }
            .dt-table td.sorting {
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
        <script defer type="text/javascript" src="/assets/js/cm.dataTable.js?' . time() . '"></script>
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

        $embed                          = '<table class="dt-table">
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
                                                console.log(settings);
                                                console.log(data);
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
                                                
                                                settings.ajax.url = url;
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
                                                    dataType : "json",
                                                    complete: function(xhr, textStatus) {
                                                     
                                                        // jQuery does not respect a 2xx with a JSON content type/data type and an empty body.
                                                        if ((xhr.status === 200 || xhr.status === 204) && xhr.responseText === "") {
                                                            xhr.responseJSON = {};
                                                            xhr.responseText = "{}";
                                                        }
                                                 
                                                    }
                                                },
                                                columns: ' . json_encode($columns_json). ',
                                                deferLoading: ' . $this->records . '
                                            });
                                        </script>';

        return $embed;
    }
}
