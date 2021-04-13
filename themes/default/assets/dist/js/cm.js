let settings = {
    "class" : {
        "main"              : "cm-main",
        "modal"             : "cm-modal",
        "xhr"               : "cm-xhr"
    },
    "modal" : {
        "container"         : "uk-modal",
        "header" : {
            "container"     : "uk-modal-header",
            "close"         : "uk-close",
            "title"         : "uk-modal-title",
            "description"   : "uk-modal-description"
        },
        "error"             : "uk-modal-error",
        "body"              : "uk-modal-body",
        "footer"            : {
            "container"     : "uk-modal-footer",
            "action"        : "uk-action"
        },
        "tokens"            : {
            "open"          : "uk-open",
            "error"         : "uk-modal-error uk-alert uk-alert-danger"
        }
    }
};

let cm = (function () {
    const _CSS                  = "css";
    const _STYLE                = "style";
    const _STRUCTURED_DATA      = "structured_data";
    const _JS                   = "js";
    const _JS_EMBED             = "js_embed";
    const _HTML                 = "html";
    const _DEBUG                = "debug";
    const _ERROR                = "error";
    const _LOADED               = "-loaded";

    const CLASS_MAIN            = settings.class.main;
    const CLASS_MODAL           = settings.class.modal;
    const CLASS_XHR             = settings.class.xhr;

    let modalLoaded             = false;

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
            if(dataResponse === undefined) {
                return;
            }
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

            if(dataResponse[_HTML] !== undefined) {
                if(querySelector === undefined && dataResponse["component"] !== undefined) {
                    querySelector = dataResponse["component"];
                }
                if(querySelector === undefined) {
                    let div = document.createElement("div");
                    div.innerHTML = dataResponse[_HTML];
                    querySelector = ((selector = div.firstChild.getAttribute("id"))
                        ? "#" + selector
                        : undefined
                    );
                }

                if(html = document.querySelector(querySelector || ("." + CLASS_MAIN))) {
                    html.innerHTML = dataResponse[_HTML];

                    guiInit();
                }
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
                            if((resp = xhr.responseText)) {
                                let respJson = JSON.parse(resp);

                                if (respJson[_DEBUG] !== undefined) {
                                    console.log(respJson[_DEBUG]);
                                }

                                if (xhr.status === 200) {
                                    if(respJson["redirect"] !== undefined) {
                                        window.location.href = respJson["redirect"];
                                    } else {
                                        resolve(respJson["data"]);
                                    }
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
        "modal" : (function() {
            let setting = {...{
                    "modal" : {
                        "container"         : "cm-modal",
                        "header" : {
                            "container"     : "cm-modal-header",
                            "close"         : "cm-close",
                            "title"         : "cm-modal-title",
                            "description"   : "cm-modal-description"
                        },
                        "error"             : "cm-modal-error",
                        "body"              : "cm-modal-body",
                        "footer"            : {
                            "container"     : "cm-modal-footer",
                            "action"        : "cm-action"
                        },
                        "tokens"            : {
                            "open"          : "cm-open",
                            "error"         : "cm-modal-error cm-alert cm-alert-danger"
                        }
                    }
                }, ...settings};

            const MODAL_OPEN            = settings.modal.tokens.open;
            const MODAL_ALERT           = settings.modal.tokens.error;
            const MODAL                 = "."           + settings.modal.container;
            const MODAL_CLOSE           = MODAL         + " ." + settings.modal.header.close;
            const MODAL_ERROR           = MODAL         + " ." + settings.modal.error;
            const MODAL_HEADER          = MODAL         + " ." + settings.modal.header.container;
            const MODAL_TITLE           = MODAL_HEADER  + " ." + settings.modal.header.title;
            const MODAL_DESCRIPTION     = MODAL_HEADER  + " ." + settings.modal.header.description;

            const MODAL_BODY            = MODAL         + " ." + settings.modal.body;
            const MODAL_FORM            = MODAL         + " form";
            const MODAL_FOOTER          = MODAL         + " ." + settings.modal.footer.container;
            const MODAL_FOOTER_ACTION   = MODAL_BODY    + " ." + settings.modal.footer.action;

            let modal = {
                "init" : function () {
                    if(!modalLoaded) {
                        modalLoaded = true;
                        if((close = document.querySelector(MODAL_CLOSE))) {
                            close.addEventListener("click", function () {
                                modal.hide();
                            });
                        }
                        if (!document.querySelector(MODAL_ERROR)) {
                            let body = document.querySelector(MODAL_BODY);
                            let error = document.createElement("DIV");

                            error.className = MODAL_ALERT;
                            error.style.display = "none";
                            body.parentNode.insertBefore(error, body);
                        }
                    }
                },
                "formAddListener" : function(url, headers = {}) {
                    if((form = document.querySelector(MODAL_FORM))) {
                        form.action = url;
                        form.addEventListener("submit", function(e) {
                            e.preventDefault();

                            modal.error.hide();

                            let that = this;
                            that.style["opacity"] = "0.5";
                            cm.api.post(form.action, new FormData(form), headers)
                                .then(function(dataResponse) {
                                    if(dataResponse["pathname"] !== undefined && window.location.pathname === dataResponse["pathname"]) {
                                        cm.inject(dataResponse);
                                        modal.hide();
                                    } else {
                                        cm.inject(dataResponse, MODAL_BODY);
                                        modal.formAddListener(form.action, headers);
                                    }
                                })
                                .catch(function (message) {
                                    modal.error.show(message);
                                }).finally(function () {
                                that.style["opacity"] = null;
                            });

                            /*cm.api.head(this.href)
                                .then(function (response) {
                                    cm.api.head(this.href)
                                });*/
                        });
                    }
                },
                "error" : {
                    "show" : function(message) {
                        let error = document.querySelector(MODAL_ERROR);
                        error.innerHTML = message;
                        error.style.display = "block";
                    },
                    "hide" : function() {
                        let error = document.querySelector(MODAL_ERROR);
                        error.innerHTML = "";
                        error.style.display = "none";
                    }
                },
                "show" : function show() {
                    let modal = document.querySelector(MODAL);
                    modal.classList.add(MODAL_OPEN);
                    modal.style["display"] = "block";
                },
                "hide" : function () {
                    let modal = document.querySelector(MODAL);
                    modal.classList.remove(MODAL_OPEN);
                    modal.style["display"] = "none";

                    this.error.hide();
                },
                "clear" : function () {
                    document.querySelector(MODAL_BODY).innerHTML    = "";
                    document.querySelector(MODAL_ERROR).innerHTML   = "";

                    if((title = document.querySelector(MODAL_TITLE))) {
                        title.innerHTML                             = "";
                    }
                    if((description = document.querySelector(MODAL_DESCRIPTION))) {
                        description.innerHTML                       = "";
                    }
                    if((footer = document.querySelector(MODAL_FOOTER))) {
                        footer.style.display                        = 'none';
                        footer.innerHTML                            = "";
                    }
                }
            }


            return {
                "open" : function(url, headers = {}) {
                    modal.init();

                    return new Promise(function (resolve, reject) {
                        modal.clear();

                        cm.api.get(url, headers)
                            .then(function (dataResponse) {
                                cm.inject(dataResponse, MODAL_BODY);
                                if(dataResponse["title"] !== undefined && (title = document.querySelector(MODAL_TITLE))) {
                                    title.innerHTML = dataResponse["title"];
                                }
                                if(dataResponse["description"] !== undefined && (description = document.querySelector(MODAL_DESCRIPTION))) {
                                    description.innerHTML = dataResponse["description"];
                                }

                                if((footer = document.querySelector(MODAL_FOOTER))) {
                                    footer.style.display = 'none';
                                    if ((action = document.querySelector(MODAL_FOOTER_ACTION))) {
                                        footer.appendChild(action);
                                        footer.style.display = 'block';
                                    }
                                }

                                modal.formAddListener(url, headers);
                                modal.show();

                                resolve(document.querySelector(MODAL));
                            })
                            .catch(function (message) {
                                let error = document.querySelector(MODAL_ERROR);
                                error.innerHTML = message;
                                error.style.display = "block";

                                console.log(message);

                                modal.show();

                                reject(message);
                            });
                    });
                },
                "close" : function() {
                    modal.hide();
                }
            }
        })()
    }

    function guiInit()
    {
        let links = document.querySelectorAll("a." + CLASS_MODAL);
        for (let i = 0; i < links.length; i++) {
            links[i].classList.remove(CLASS_MODAL);
            links[i].classList.add(CLASS_MODAL + _LOADED);
            links[i].addEventListener("click", function (e) {
                e.preventDefault();

                let that = this;
                that.style["opacity"] = "0.5";
                cm.modal.open(this.href)
                    .then(function() {
                        that.style["opacity"] = null;
                    })
                    .finally(function() {
                        that.style["opacity"] = null;
                    });
            });
        }

        links = document.querySelectorAll("a." + CLASS_XHR);
        for (let i = 0; i < links.length; i++) {
            links[i].addEventListener("click", function (e) {
                e.preventDefault();

                let that = this;
                that.style["opacity"] = "0.5";
                cm.api.get(this.href)
                    .then(function(dataResponse) {
                        cm.inject(dataResponse);
                        that.style["opacity"] = null;
                    })
                    .finally(function() {
                        that.style["opacity"] = null;
                    });
            });
        }
    }

    document.addEventListener("DOMContentLoaded", function(event) {
        guiInit();
    });

    return that;
})();