let cm = (function () {
    const _CSS              = "css";
    const _STYLE            = "style";
    const _STRUCTURED_DATA  = "structured_data";
    const _JS               = "js";
    const _JS_EMBED         = "js_embed";
    const _HTML             = "html";
    const _DEBUG            = "debug";
    const _ERROR            = "error";

    const CLASS_MODAL       = "modal";

    let modalLoaded = false;

    let isLoadedResource = function (resource, type) {
        const ATTR = {
            "link"      : "href",
            "script"    : "src"
        };

        return null !== document.querySelector(type + "[" + ATTR[type] + "^='" + resource.split("?")[0] + "']");
    };


    let that = {
        "getURLParameter" : function (name) {
            let tmp = (RegExp(name.replace(/\[/g, "\\[").replace(/\]/g, "\\]") + '=' + '(.+?)(&|$)').exec(location.search) || [, null])[1];
            if (tmp !== null) {
                return decodeURIComponent(tmp);
            } else {
                return null;
            }
        },
        "inject" : function (dataResponse, querySelector) {
            if (dataResponse[_CSS] !== undefined && dataResponse[_CSS].length) {
                for (let key in dataResponse[_CSS]) {
                    if (!dataResponse[_CSS].hasOwnProperty(key) || isLoadedResource(key, "link")) {
                        continue;
                    }

                    let css = document.createElement("link");
                    css.type = "text/css";
                    css.rel = "stylesheet";
                    css.href = key;
                    if (dataResponse[_CSS][key]) {
                        css.media = dataResponse[_CSS][key];
                    }
                    document.head.appendChild(css);
                }
            }
            if (dataResponse[_STYLE] !== undefined && dataResponse[_STYLE].length) {
                let style = document.createElement(_STYLE);
                style.innerHTML = dataResponse[_STYLE];
                document.head.appendChild(style);
            }
            if (dataResponse[_STRUCTURED_DATA] !== undefined && dataResponse[_STRUCTURED_DATA].length) {
                let script = document.createElement("script");
                script.type = "application/ld+json";
                script.innerHTML = dataResponse[_STRUCTURED_DATA];
                document.head.appendChild(script);
            }
            if (dataResponse[_JS] !== undefined && dataResponse[_JS].length) {
                for (let key in dataResponse[_JS]) {
                    if (!dataResponse[_JS].hasOwnProperty(key) || isLoadedResource(key, "script")) {
                        continue;
                    }

                    let script = document.createElement("script");
                    script.type = "application/javascript";
                    script.src = key;
                    document.head.appendChild(script);
                }
            }
            if (dataResponse[_JS_EMBED] !== undefined && dataResponse[_JS_EMBED].length) {
                let script = document.createElement("script");
                script.type = "application/javascript";
                script.innerHTML = dataResponse[_JS_EMBED];
                document.head.appendChild(script);
            }

            if (querySelector !== undefined && dataResponse[_HTML] !== undefined && (html = document.querySelector(querySelector))) {
                html.innerHTML = dataResponse[_HTML];
            }
        },
        "api" : (function () {
            let xhr = new XMLHttpRequest();

            function request(method, url, headers, data) {
                return new Promise(function (resolve, reject) {
                    xhr.abort();

                    xhr.open(method, url);
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    for (let key in headers) {
                        if (headers.hasOwnProperty(key)) {
                            xhr.setRequestHeader(key, headers[key]);
                        }
                    }
                    xhr.onreadystatechange = function () {
                        if (xhr.readyState === 4) {
                            if(resp = xhr.responseText) {
                                let respJson = JSON.parse(resp);

                                if (respJson[_DEBUG] !== undefined) {
                                    console.log(respJson[_DEBUG]);
                                }

                                if (xhr.status === 200) {
                                    resolve(respJson["data"]);
                                    console.log("xhr done successfully");
                                } else {
                                    reject(respJson[_ERROR]);
                                    console.log("xhr failed");
                                }
                            } else {
                                console.log("xhr response empty");
                            }
                        } else {
                            console.log("xhr processing going on");
                        }
                    }
                    xhr.send(data);
                });
            }

            return {
                "get": function (url, headers) {
                    return request("GET", url, headers);
                },
                "post": function (url, data, headers) {
                    return request("POST", url, headers, data);
                },
                "put": function (url, headers) {
                    return request("PUT", url, headers);
                },
                "patch": function (url, headers) {
                    return request("PATCH", url, headers);
                },
                "delete": function (url, headers) {
                    return request("DELETE", url, headers);
                }
            };
        })(),
        "modal" : function(settings = {}) {
            const MODAL_OPEN        = settings.open                                 || "cm-open";
            const MODAL_ALERT       = settings.alert                                || "cm-modal-error cm-alert cm-alert-danger";
            const MODAL             = "."           + (settings.modal               || "cm-modal");
            const MODAL_CLOSE       = MODAL         + " ." + (settings.close        || "cm-close");
            const MODAL_ERROR       = MODAL         + " ." + (settings.error        || "cm-modal-error");
            const MODAL_HEADER      = MODAL         + " ." + (settings.header       || "cm-modal-header");
            const MODAL_TITLE       = MODAL_HEADER  + " ." + (settings.title        || "cm-modal-title");
            const MODAL_DESCRIPTION = MODAL_HEADER  + " ." + (settings.description  || "cm-modal-description");

            const MODAL_BODY        = MODAL         + " ." + (settings.body         || "cm-modal-body");
            const MODAL_FORM        = MODAL_BODY    + " form";
            const MODAL_ACTION      = MODAL_BODY    + " ." + (settings.action       || "cm-action");
            const MODAL_FOOTER      = MODAL         + " ." + (settings.footer       || "cm-modal-footer");

            if(!modalLoaded) {
                modalLoaded     = true;

                document.querySelector(MODAL_CLOSE).addEventListener("click", function () {
                    modalHide();
                });

                if(!document.querySelector(MODAL_ERROR)) {
                    let body = document.querySelector(MODAL_BODY);
                    let error = document.createElement("DIV");

                    error.className = MODAL_ALERT;
                    error.style.display = "none";
                    body.parentNode.insertBefore(error, body);
                }

            }
            let that = {
                "open" : function(url, headers) {
                    return new Promise(function (resolve, reject) {
                        cm.api.get(url, headers)
                        .then(function (dataResponse) {
                            cm.inject(dataResponse, MODAL_BODY);
                            if(dataResponse["title"] !== undefined && (title = document.querySelector(MODAL_TITLE))) {
                                title.innerHTML = dataResponse["title"];
                            }
                            if(dataResponse["description"] !== undefined && (description = document.querySelector(MODAL_DESCRIPTION))) {
                                description.innerHTML = dataResponse["description"];
                            }

                            if(footer = document.querySelector(MODAL_FOOTER)) {
                                footer.style.display = 'none';
                                if (action = document.querySelector(MODAL_ACTION)) {
                                    footer.appendChild(action);
                                    footer.style.display = 'block';
                                }
                            }

                            formAddListener(url);

                            modalShow();
                            resolve(document.querySelector(MODAL));
                        })
                        .catch(function (status, message) {
                            let error = document.querySelector(MODAL_ERROR);
                            error.innerHTML = message;
                            error.style.display = "block";

                            console.log(status, message);

                            modalShow();
                            reject(message);
                        });
                    });
                }
            }

            function formAddListener(url) {
                if(form = document.querySelector(MODAL_FORM)) {
                    form.action = url;
                    form.addEventListener("submit", function(e) {
                        e.preventDefault();

                        errorHide();
                        let that = this;
                        that.style["opacity"] = "0.5";
                        cm.api.post(form.action, new FormData(form))
                            .then(function(dataResponse) {
                                cm.inject(dataResponse, MODAL_BODY);
                                formAddListener(form.action);
                            })
                            .catch(function (message) {
                                errorShow(message);
                            }).finally(function () {
                            that.style["opacity"] = null;
                        });

                        /*cm.api.head(this.href)
                            .then(function (response) {
                                cm.api.head(this.href)
                            });*/
                    });
                }
            }

            function modalShow() {
                let modal = document.querySelector(MODAL);
                modal.classList.add(MODAL_OPEN);
                modal.style["display"] = "block";
            }
            function modalHide() {
                let modal = document.querySelector(MODAL);
                modal.classList.remove(MODAL_OPEN);
                modal.style["display"] = "none";
            }

            function errorShow(message) {
                let error = document.querySelector(MODAL_ERROR);
                error.innerHTML = message;
                error.style.display = "block";
            }
            function errorHide(message) {
                let error = document.querySelector(MODAL_ERROR);
                error.innerHTML = "";
                error.style.display = "none";
            }
            return that;
        }
    }

    function gui()
    {
        let links = document.querySelectorAll("a." + CLASS_MODAL);
        for (let i = 0; i < links.length; i++) {
            links[i].addEventListener("click", function (e) {
                e.preventDefault();

                let that = this;
                that.style["opacity"] = "0.5";
                cm.modal({
                    "open"          : "uk-open",
                    "alert"         : "uk-modal-error uk-alert uk-alert-danger",
                    "modal"         : "uk-modal",
                    "close"         : "uk-close",
                    "error"         : "uk-modal-error",
                    "header"        : "uk-modal-header",
                    "title"         : "uk-modal-title",
                    "description"   : "uk-modal-description",
                    "body"          : "uk-modal-body",
                    "action"        : "uk-action",
                    "footer"        : "uk-modal-footer"
                }).open(this.href)
                    .then(function(modal) {
                        that.style["opacity"] = null;
                    });
            });
        }
    }

    document.addEventListener("DOMContentLoaded", function(event) {
        gui();
    });

    return that;
})();