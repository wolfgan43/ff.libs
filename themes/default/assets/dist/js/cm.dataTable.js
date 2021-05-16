cm.dataTable = (function () {
    function submit(dt, url) {
        let api = new URL(url);
        api.searchParams.set("component", dt.id);

        dt.style["opacity"] = "0.5";
        cm.api.post(api.toString())
            .then(function(dataResponse) {
                let columns = {};
                let TH = dt.querySelectorAll("THEAD TH");
                for(let i = 0; i < TH.length; i++) {
                    columns[TH[i].getAttribute("data-id")] = TH[i].classList.contains("sort");
                }

                let TR = '';
                for(let i = 0; i < dataResponse.length; i++) {
                    TR += '<tr class="' + (i % 2 === 0 ? "odd": "even") + '">';
                    for (const property in columns) {
                        TR += '<td' + (columns[property] ? ' class="sorting"' : '') + '>' + dataResponse[i][property] + '</td>';
                    }
                    TR += '<tr>';
                }

                dt.querySelector("tbody").innerHTML = TR;
                //cm.inject(dataResponse, dt.getAttribute("data-component"));
            })
            .catch(function(errorMessage) {
                let error = dt.querySelector("." + settings.class.error);
                if (error) {
                    error.innerHTML = errorMessage;
                } else {
                    let error = document.createElement("DIV");

                    error.className = settings.class.error + " " + settings.tokens.error;
                    error.innerHTML = errorMessage;

                    dt.insertBefore(error, dt.firstChild)
                }
            })
            .finally(function() {
                dataTable(document.getElementById("dt/user"));
                dt.style["opacity"] = null;
            });
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

    function run(dt, url) {
        if(dt.classList.contains("cm-xhr")) {
            window.history.pushState({}, "", url);
            submit(dt, url);
        } else {
            window.location.href = url;
        }
    }


    let self = {
        "page" : function(dataTable, number) {
            run(dataTable, url("page", number));
        },
        "order" : function(dataTable, column, dir) {
            run(dataTable, url("order[" + column + "]", dir));
        },
        "length" : function(dataTable, number) {
            run(dataTable, url("length", number));
        },
        "search" : function(dataTable, query) {
            run(dataTable, url("search", query));
        }
    };

    function dataTable(dt) {
        let length  = dt.querySelectorAll("SELECT[name='length']");
        for (let i = 0; i < length.length; i++) {
            length[i].addEventListener("change", function () {
                run(dt, url("length", this.value));
            });
        }
        let search  = dt.querySelectorAll("INPUT[name='search']");
        for (let i = 0; i < search.length; i++) {
            search[i].addEventListener("change", function () {
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
    }

    function guiInit() {
        let dt  = document.querySelectorAll(".dt-wrapper");
        for (let i = 0; i < dt.length; i++) {
            dataTable(dt[i]);
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        guiInit();
    });

    return self;
})();