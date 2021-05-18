cm.dataTable = (function () {
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

    function draw(dt, response) {
        const INFO = "Showing [start] to [length] of [rfilter] entries";
        const INFO_FILTERED = " (filtered from [rtotal] total entries)";
        const RDIR = {"asc" : "desc", "desc": "asc"};
        const SORT = "sort";
        const ODD = "odd";
        const EVEN = "even";

        function drawOrder(order) {
            let url = new URL(window.location.href);

            for (let i = 0; i < order.length; i++) {
                order[i].parentNode.removeAttribute("class");
                if((dir = url.searchParams.get(SORT + "[" + i + "]"))) {
                    order[i].parentNode.setAttribute("class", SORT + " " + dir);
                    url.searchParams.set(SORT + "[" + i + "]", RDIR[dir]);
                    order[i].href = url.toString();
                }
            }
        }
        function drawPage(paging, length) {
            let url = new URL(window.location.href);
            let page = parseInt(url.searchParams.get("page") || 1);
            let page_tot = Math.ceil(response.recordsFiltered / parseInt(length));

            let prev = "";
            let pages = "";
            let next = "";

            if(page_tot >= 1) {
                let page_prev = page - 1;
                let page_next = page + 1;

                url.searchParams.set("page", page_prev);
                prev = (
                    page <= 1 || page > page_tot
                    ? '<span class="prev">Previous</span>'
                    : '<a href="' + url.toString() + '" class="prev">Previous</a>'
                );

                url.searchParams.set("page", page_next);
                next = (
                    page < 1 || page >= page_tot
                    ? '<span class="next">Next</span>'
                    : '<a href="' + url.toString() + '" class="next">Next</a>'
                );

                for (let i = 1; i <= page_tot; i++) {
                    url.searchParams.set("page", i);

                    pages += (
                        i == page
                        ? '<span class="page">' + i + '</span>'
                        : '<a href="' + url.toString() + '" class="page' + (i == page ? " current" : "") + '">' + i + '</a>'
                    );
                }
            }

            for (let i = 0; i < paging.length; i++) {
                paging[i].innerHTML = prev + pages + next;
                /*paging[i].querySelectorAll("A").addEventListener("click", function (e) {
                    e.preventDefault();

                    run(dt, this.href);
                });*/
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
            if(response.recordsFiltered != response.recordsTotal) {
                content += INFO_FILTERED.replace("[rtotal]", response.recordsTotal);
            }
            for (let i = 0; i < info.length; i++) {
                info[i].innerHTML = content;
            }
        }

        function drawBody(TH, tBody) {
            let columns = {};

            for(let i = 0; i < TH.length; i++) {
                columns[TH[i].getAttribute("data-id")] = TH[i].classList.contains("sort");
            }

            let TR = '';
            for(let i = 0; i < response.data.length; i++) {
                TR += '<tr class="' + (i % 2 === 0 ? ODD: EVEN) + '">';
                for (const property in columns) {
                    TR += '<td' + (columns[property] ? ' class="' + SORT + '"' : '') + '>' + (response.data[i][property] || "") + '</td>';
                }
                TR += '<tr>';
            }

            tBody.innerHTML = TR;
        }

        drawOrder(dt.querySelectorAll("THEAD TH a"));
        drawBody(dt.querySelectorAll("THEAD TH"), dt.querySelector("TBODY"));
        drawInfo(dt.querySelectorAll(".dt-info"), dt.querySelector(".dt-length SELECT").value);
        drawPage(dt.querySelectorAll(".dt-paginate"), dt.querySelector(".dt-length SELECT").value);
    }

    let self = {
        "page" : function(dataTable, number) {
            run(dataTable, url("page", number));
        },
        "sort" : function(dataTable, column, dir) {
            run(dataTable, url("sort[" + column + "]", dir));
        },
        "length" : function(dataTable, number) {
            run(dataTable, url("length", number));
        },
        "search" : function(dataTable, query) {
            run(dataTable, url("search", query));
        }
    };

    function dataTable(dt) {
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
/*
        let length  = dt.querySelectorAll("SELECT[name='length']");
        for (let i = 0; i < length.length; i++) {
            length[i].addEventListener("change", function (e) {
                run(dt, url("length", this.value));
            });
        }
        let search  = dt.querySelectorAll("INPUT[name='search']");
        for (let i = 0; i < search.length; i++) {
            search[i].addEventListener("change", function (e) {
                run(dt, url("search", this.value));
            });
        }


        let order   = dt.querySelectorAll("THEAD TH a");
        for (let i = 0; i < order.length; i++) {
            order[i].addEventListener("click", function (e) {
                e.preventDefault();

                run(dt, this.href);
            });
        }
        let paging  = dt.querySelectorAll("a.prev, a.page, a.next");
        for (let i = 0; i < paging.length; i++) {
            paging[i].addEventListener("click", function (e) {
                e.preventDefault();

                run(dt, this.href);
            });
        }
        */
    }

    function guiInit() {
        let dt  = document.querySelectorAll(".dt-wrapper");
        for (let i = 0; i < dt.length; i++) {
            dataTable(dt[i]);
        }
    }

    document.addEventListener("DOMContentLoaded", function(event) {
        guiInit();
    });

    return self;
})();