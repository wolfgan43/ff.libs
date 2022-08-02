cm.dataTable = (function () {
    let settings = {
        "class" : {
            "component"         : "dt-component",
            "error"             : "cm-error",
            "action"            : "dt-action",
            "title"             : "dt-title",
            "description"       : "dt-description",
            "wrapper"           : "dataTable-wrapper",
            "top"               : "dataTable-top",
                "length"        : "dataTable-dropdown",
                "search"        : "dataTable-search",
            "container"         : "dataTable-container",
                "table"         : "dataTable-table",
                "empty"         : "dt-empty",
            "bottom"            : "dataTable-bottom",
                "info"          : "dataTable-info",
                "paginate"      : "dataTable-pagination"
        }
    };
    let self = {
        "page" : function(id, number) {
            dataTable(document.getElementById(id)).run("page", number);
        },
        "sort" : function(id, column, dir) {
            dataTable(document.getElementById(id)).run("sort[" + column + "]", dir);
        },
        "length" : function(id, number) {
            dataTable(document.getElementById(id)).run("length", number);
        },
        "search" : function(id, query) {
            dataTable(document.getElementById(id)).run("search", query);
        }
    };

    function dataTable(dt) {
        function loadJs() {
            if(js = dt.querySelector(".dt-js")) {
                eval(js.innerText);
            }
        }

        function url(name, value) {
            let url = new URL(window.location);
            if(value) {
                url.searchParams.set(name, value);
            } else {
                url.searchParams.delete(name);
            }
            return url.toString();
        }

        function run(url) {
            function submit(url) {
                let api = new URL(url);
                api.searchParams.set("component", dt.id);
                if((key = dt.querySelector("TBODY").getAttribute("data-key"))) {
                    api.searchParams.set("key", key);
                }

                dt.style["opacity"] = "0.5";
                cm.api.search(api.toString())
                    .then(function(response) {
                        cm.alertBox(dt).clear();
                        draw(response);
                    })
                    .catch(function(errorMessage) {
                        cm.alertBox(dt).error(errorMessage);
                    })
                    .finally(function() {
                        dt.style["opacity"] = null;
                    });
            }

            if(dt.classList.contains(cm.settings().class.xhr)) {
                window.history.pushState({}, "", url);
                submit(url);
            } else {
                window.location.href = url;
            }
        }

        function addListener(selector, event, callback)
        {
            let nodes = dt.querySelectorAll(selector);
            for (let i = 0; i < nodes.length; i++) {
                nodes[i].addEventListener(event, function (e) {
                    e.preventDefault();

                    run(callback(this));
                });
            }
        }


        function draw(response) {
            const INFO = "Showing [start] to [length] of [rfilter] entries";
            const INFO_FILTERED = " (filtered from [rtotal] total entries)";
            const DEFAULT_DIR = "asc";
            const RDIR = {"asc" : "desc", "desc": "asc"};
            const SORT = "sort";
            const ODD = "odd";
            const EVEN = "even";

            const RECORD_LIMIT = (
                dt.querySelector("." + settings.class.length + " SELECT")
                    ? dt.querySelector("." + settings.class.length + " SELECT").value
                    : 25
            );

            function drawOrder(columns) {
                let url = new URL(window.location.href);
                let dir = DEFAULT_DIR;
                let index, sort = null;

                for (let i = 0; i < columns.length; i++) {
                    sort = SORT + "[" + i + "]";
                    if(url.searchParams.get(sort)) {
                        index = i;
                        dir = url.searchParams.get(sort);
                        url.searchParams.delete(sort);
                        break;
                    }
                }
                for (let i = 0; i < columns.length; i++) {
                    if(a = columns[i].querySelector("a")) {
                        sort = SORT + "[" + i + "]";
                        columns[i].removeAttribute("class");
                        if (i === index) {
                            columns[i].setAttribute("class", "dataTable-sorter" + " " + dir);
                            url.searchParams.set(sort, RDIR[dir]);
                        } else {
                            url.searchParams.set(sort, dir);
                        }

                        a.href = url.toString();
                        url.searchParams.delete(sort);
                    }
                }
            }
            function drawPage(paging, length) {
                let url = new URL(window.location.href);
                let page = parseInt(url.searchParams.get("page") || 1);
                let page_tot = Math.ceil(response.recordsFiltered / parseInt(length));

                let pages = "";

                let page_prev = page - 1;
                let page_next = page + 1;

                url.searchParams.set("page", page_prev);
                let prev = '<li>' + (
                    page <= 1 || page > page_tot
                        ? '<span class="prev">Previous</span>'
                        : '<a href="' + url.toString() + '" class="prev">Previous</a>'
                ) + '</li>';

                url.searchParams.set("page", page_next);
                let next = '<li>' + (
                    page < 1 || page >= page_tot
                        ? '<span class="next">Next</span>'
                        : '<a href="' + url.toString() + '" class="next">Next</a>'
                ) + '</li>';

                for (let i = 1; i <= page_tot; i++) {
                    url.searchParams.set("page", i);

                    pages += '<li>' + (
                        i === page
                            ? '<span class="page">' + i + '</span>'
                            : '<a href="' + url.toString() + '" class="page' + '">' + i + '</a>'
                    ) + '</li>';
                }

                for (let i = 0; i < paging.length; i++) {
                    paging[i].innerHTML = '<ul>' + prev + pages + next + '</ul>';

                    addListener("a.prev, a.page, a.next", "click", function(self) {
                        return self.href;
                    });
                }
            }
            function drawInfo(info, length) {
                let url = new URL(window.location.href);

                let page = url.searchParams.get("page") || 1;
                let start                    = (length * (page - 1));
                if (start < 0) {
                    start                = 0;
                }

                if (start > response.recordsFiltered) {
                    start = 0;
                    length = 0;
                } else {
                    if(start + parseInt(length) > response.recordsFiltered) {
                        length = response.recordsFiltered;
                    }
                    start = start + 1;
                }

                let content = INFO.replace("[start]", start).replace("[length]", length).replace("[rfilter]", response.recordsFiltered);
                if(response.recordsFiltered !== response.recordsTotal) {
                    content += INFO_FILTERED.replace("[rtotal]", response.recordsTotal);
                }
                for (let i = 0; i < info.length; i++) {
                    info[i].innerHTML = content;
                }
            }

            function drawBody(TH, tBody) {
                if(response.data.length) {
                    let TR = '';
                    let tplButton = dt.querySelector(".dt-btn");
                    let recordKey = tBody.getAttribute("data-key");
                    for (let i = 0; i < response.data.length; i++) {
                        let attrId = "";
                        let buttons = "";
                        if(recordKey && response.keys && response.keys[i]) {
                            attrId = ' data-id="' + response.keys[i] + '"';
                            buttons = drawButton(tplButton, recordKey, response.keys[i]);
                        }

                        TR += '<tr' + attrId + ' class="' + (i % 2 === 0 ? ODD : EVEN) + '">';

                        let tplRow = dt.querySelector(".dt-row").innerHTML;
                        for (const field in response.data[i]) {
                            tplRow = tplRow.replace("{" + field + "}", response.data[i][field] || "");
                        }
                        TR += tplRow;
                        TR += buttons;
                        TR += '</tr>';
                    }

                    tBody.innerHTML = TR;

                    cm.guiInit(tBody);
                } else {
                    drawBodyEmpty(TH, tBody, "Try to Change Page!");
                }
            }

            function drawButton(tplButton, recordKey, recordId) {
                function updateLinksInHTML(html) {
                    //let regex = /href\s*=\s*"([^"?#]*)(?:\?([^"#]*)&?)?(#[^"]*)?"/gi;
                    let regex = /href\s*=\s*"([^"]*)"/gi;
                    let link;

                    while ((link = regex.exec(html)) !== null) {
                        let url = new URL(link[1], top.location.href);
                        url.searchParams.set(recordKey, recordId);
                        if (url.searchParams.has("redirect")) {
                            url.searchParams.set("redirect", window.location.pathname + window.location.search + window.location.hash);
                        }
                        html = html.replace(link[1], url.toString());
                    }

                    return html;
                }

                return '<td>' + updateLinksInHTML(tplButton.innerHTML.replace("({'", "({'" + recordKey + "':'" + recordId + "','")) + '</td>';
            }

            function drawBodyEmpty(TH, tBody, message) {
                tBody.innerHTML = '<tr class="' + settings.class.empty + '"><td colspan="' + TH.length + '">No matching records found. ' + (message || "") + '</td></tr>';
            }

            if(response) {
                drawBody(dt.querySelectorAll("THEAD TH[data-id]"), dt.querySelector("TBODY"));
                //recordUrl();
            } else {
                response = {
                    recordsFiltered : 0,
                    recordsTotal : 0
                };

                drawBodyEmpty(dt.querySelectorAll("THEAD TH"), dt.querySelector("TBODY"));
            }

            drawOrder(dt.querySelectorAll("THEAD TH"));
            drawInfo(dt.querySelectorAll("." + settings.class.info), RECORD_LIMIT);
            drawPage(dt.querySelectorAll("." + settings.class.paginate), RECORD_LIMIT);

            loadJs();
        }

        function recordUrl() {
            let tbody       = dt.querySelector("TBODY");
            let recordUrl   = tbody.getAttribute("data-url");
            let recordKey   = tbody.getAttribute("data-key");

            if(recordUrl && recordKey) {
                let nodes = dt.querySelectorAll("TBODY TR[data-key]")
                for (let i = 0; i < nodes.length; i++) {
                    nodes[i].style["cursor"] = "pointer";
                    nodes[i].addEventListener("click", function (e) {
                        e.preventDefault();

                        window.location.href = recordUrl + "?" + recordKey + "=" + this.getAttribute("data-id") + "&redirect=" + encodeURIComponent(window.location.pathname + window.location.search + window.location.hash );
                    });
                }
            }
        }

        //recordUrl();



        addListener("SELECT[name='length']", "change", function(self) {
            return url("length", self.value);
        });
        addListener("INPUT[name='search']", "change", function(self) {
            return url("search", self.value);
        });
        addListener("THEAD TH a", "click", function(self) {
            return self.href;
        });
        addListener("a.prev, a.page, a.next", "click", function(self) {
            return self.href;
        });

        loadJs();

        return {
            "run" : function(name, value) {
                run(url(name, value));
            }
        };
    }

    function guiInit() {
        let dt  = document.querySelectorAll("." + settings.class.component);
        for (let i = 0; i < dt.length; i++) {
            dataTable(dt[i]);
        }
    }

    cm.onReady(guiInit);

    return self;
})();