cm.dataTable = (function () {
    let settings = {
        "class" : {
            "component"         : "dt-wrapper",
            "error"             : "cm-error",
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
        function url(name, value) {
            let url = new URL(window.location);
            if(value) {
                url.searchParams.set(name, value);
            } else {
                url.searchParams.delete(name);
            }
            return url.toString();
        }

        function run(dt, url) {
            function submit(dt, url) {
                let api = new URL(url);
                api.searchParams.set("component", dt.id);

                dt.style["opacity"] = "0.5";
                cm.api.search(api.toString())
                    .then(function(response) {
                        draw(dt, response);
                    })
                    .catch(function(errorMessage) {
                        cm.error(dt).set(errorMessage);
                    })
                    .finally(function() {
                        dt.style["opacity"] = null;
                    });
            }

            if(dt.classList.contains(cm.settings.class.xhr)) {
                window.history.pushState({}, "", url);
                submit(dt, url);
            } else {
                window.location.href = url;
            }
        }

        function addListener(selector, event, callback)
        {
            let length  = dt.querySelectorAll(selector);
            for (let i = 0; i < length.length; i++) {
                length[i].addEventListener(event, function (e) {
                    e.preventDefault();

                    run(dt, callback(this));
                });
            }
        }


        function draw(dt, response) {
            const INFO = "Showing [start] to [length] of [rfilter] entries";
            const INFO_FILTERED = " (filtered from [rtotal] total entries)";
            const RDIR = {"asc" : "desc", "desc": "asc"};
            const SORT = "sort";
            const ODD = "odd";
            const EVEN = "even";

            function drawOrder(order) {
                let url = new URL(window.location.href);
                let index, dir, sort = null;

                for (let i = 0; i < order.length; i++) {
                    sort = SORT + "[" + i + "]";
                    if(url.searchParams.get(sort)) {
                        index = i;
                        dir = url.searchParams.get(sort);
                        url.searchParams.delete(sort);
                        break;
                    }
                }
                for (let i = 0; i < order.length; i++) {
                    sort = SORT + "[" + i + "]";
                    order[i].parentNode.removeAttribute("class");
                    if(i === index) {
                        order[i].parentNode.setAttribute("class", "dataTable-sorter" + " " + dir);
                        url.searchParams.set(sort, RDIR[dir]);
                    } else {
                        url.searchParams.set(sort, dir);
                    }

                    order[i].href = url.toString();
                    url.searchParams.delete(sort);
                }
            }
            function drawPage(paging, length) {
                let url = new URL(window.location.href);
                let page = parseInt(url.searchParams.get("page") || 1);
                let page_tot = Math.ceil(response.recordsFiltered / parseInt(length));

                let prev = "";
                let pages = "";
                let next = "";

                let page_prev = page - 1;
                let page_next = page + 1;

                url.searchParams.set("page", page_prev);
                prev = '<li>' + (
                    page <= 1 || page > page_tot
                        ? '<span class="prev">Previous</span>'
                        : '<a href="' + url.toString() + '" class="prev">Previous</a>'
                ) + '</li>';

                url.searchParams.set("page", page_next);
                next = '<li>' + (
                    page < 1 || page >= page_tot
                        ? '<span class="next">Next</span>'
                        : '<a href="' + url.toString() + '" class="next">Next</a>'
                ) + '</li>';

                for (let i = 1; i <= page_tot; i++) {
                    url.searchParams.set("page", i);

                    pages += '<li>' + (
                        i === page
                            ? '<span class="page">' + i + '</span>'
                            : '<a href="' + url.toString() + '" class="page' + (i === page ? " current" : "") + '">' + i + '</a>'
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

                    let columns = {};

                    for (let i = 0; i < TH.length; i++) {
                        columns[TH[i].getAttribute("data-id")] = TH[i].classList.contains(SORT);
                    }

                    let TR = '';
                    for (let i = 0; i < response.data.length; i++) {
                        TR += '<tr class="' + (i % 2 === 0 ? ODD : EVEN) + '">';
                        for (const property in columns) {
                            TR += '<td' + (columns[property] ? ' class="' + SORT + '"' : '') + '>' + (response.data[i][property] || "") + '</td>';
                        }
                        TR += '<tr>';
                    }

                    tBody.innerHTML = TR;
                } else {
                    drawBodyEmpty(TH, tBody, "Try to Change Page!");
                }
            }

            function drawBodyEmpty(TH, tBody, message) {
                tBody.innerHTML = '<tr class="' + settings.class.empty + '"><td colspan="' + TH.length + '">No matching records found. ' + message + '</td></tr>';
            }

            if(response) {
                drawOrder(dt.querySelectorAll("THEAD TH a"));
                drawBody(dt.querySelectorAll("THEAD TH"), dt.querySelector("TBODY"));
                drawInfo(dt.querySelectorAll("." + settings.class.info), dt.querySelector("." + settings.class.length + " SELECT").value);
                drawPage(dt.querySelectorAll("." + settings.class.paginate), dt.querySelector("." + settings.class.length + " SELECT").value);
            } else {
                response = {
                    recordsFiltered : 0,
                    recordsTotal : 0
                };

                drawOrder(dt.querySelectorAll("THEAD TH a"));
                drawBodyEmpty(dt.querySelectorAll("THEAD TH"), dt.querySelector("TBODY"));
                drawInfo(dt.querySelectorAll("." + settings.class.info), dt.querySelector(".dt" + settings.class.length + " SELECT").value);
                drawPage(dt.querySelectorAll("." + settings.class.paginate), dt.querySelector("." + settings.class.length + " SELECT").value);
            }
        }


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

        return {
            "run" : function(name, value) {
                run(dt, url(name, value));
            }
        };
    }

    function guiInit() {
        let dt  = document.querySelectorAll("." + settings.class.component);
        for (let i = 0; i < dt.length; i++) {
            dataTable(dt[i]);
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        guiInit();
    });

    return self;
})();