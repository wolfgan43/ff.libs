let cm = (function () {
    const _CSS                  = "css";
    const _STYLE                = "style";
    const _STRUCTURED_DATA      = "structured_data";
    const _JS                   = "js";
    const _JS_EMBED             = "js_embed";
    const _HTML                 = "html";
    const _COMPONENT            = "component";
    const _DEBUG                = "debug";
    const _ERROR                = "error";
    const _LOADED               = "-loaded";

    let modalLoaded             = false;
    let settings = {
        "class" : {
            "main"              : "cm-main",
            "modal"             : "cm-modal",
            "xhr"               : "cm-xhr",
            "error"             : "cm-error"
        },
        "modal" : {
            "open"              : "cm-open",
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
            }
        },
        "tokens"                : {
            "error"             : "alert alert-danger"
        }
    };

    let isLoadedResource = function (resource, type) {
        const ATTR = {
            "link"      : "href",
            "script"    : "src"
        };

        return null !== document.querySelector(type + "[" + ATTR[type] + "^='" + resource.split("?")[0] + "']");
    };


    let self = {
        "defaults" : settings,
        "getURLParameter" : function (name) {
            let tmp = (RegExp(name.replace(/\[/g, "\\[").replace(/\]/g, "\\]") + '=' + '(.+?)(&|$)').exec(location.search) || [, null])[1];
            if (tmp !== null) {
                return decodeURIComponent(tmp);
            } else {
                return null;
            }
        },
        "cookie" : (function() {
            return {
                "get": function (name) {
                    let v = document.cookie.match('(^|;) ?' + name + '=([^;]*)(;|$)');
                    return v ? v[2] : null;
                },
                "set": function (name, value) {
                    let d = new Date;
                    d.setTime(d.getTime() + 24 * 60 * 60 * 1000 * days);
                    document.cookie = name + "=" + value + ";path=/;expires=" + d.toGMTString();
                },
                "remove": function (name) {
                    set(name, '', -1);
                }
            };
        })(),
        "inject" : function (dataResponse, querySelector) {
            if(dataResponse === undefined) {
                return;
            }
            if (typeof dataResponse[_CSS] === "object") {
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
            if (typeof dataResponse[_JS] === "object") {
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
                if(querySelector) {
                    if((html = (document.querySelector(querySelector)))) {
                        html.innerHTML = dataResponse[_HTML];

                        guiInit();
                    }
                } else if(dataResponse[_COMPONENT] !== undefined) {
                    if((html = (document.querySelector(dataResponse[_COMPONENT])))) {
                        html.parentNode.replaceChild(dataResponse[_HTML], html);

                        guiInit();
                    } else {
                        console.error("querySelector error: declare component in response or in attribute data-component.", dataResponse);
                    }
                } else {
                    let div = document.createElement("div");
                    div.innerHTML = dataResponse[_HTML];
                    if((selector = div.firstChild.getAttribute("id")) && (html = (document.querySelector("#" + selector)))) {
                        html.parentNode.replaceChild(div, html);

                        guiInit();
                    } else if((html = (document.querySelector("." + settings.class.main)))) {
                        html.innerHTML = dataResponse[_HTML];

                        guiInit();
                    }
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
                    if((csrf = cm.cookie.get("csrf"))) {
                        headers = {...headers, ...{"X-CSRF-TOKEN" : csrf}};
                    }

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
            let modal = {
                "init" : function () {
                    if(!modalLoaded) {
                        modalLoaded = true;
                        if((close = document.querySelector("." + settings.modal.container + " ." + settings.modal.header.close))) {
                            close.addEventListener("click", function () {
                                modal.hide();
                            });
                        }
                        if (!document.querySelector("." + settings.modal.container + " ." + settings.modal.error)) {
                            let body = document.querySelector("." + settings.modal.container + " ." + settings.modal.body);
                            let error = document.createElement("DIV");

                            error.className = settings.modal.error + " " + settings.tokens.error;
                            error.style.display = "none";
                            body.parentNode.insertBefore(error, body);
                        }
                    }
                },
                "formAddListener" : function(url, headers = {}) {
                    if((form = document.querySelector("." + settings.modal.container + " form"))) {
                        form.action = url;
                        form.addEventListener("submit", function(e) {
                            e.preventDefault();

                            modal.error.hide();

                            let self = this;
                            self.style["opacity"] = "0.5";
                            cm.api.post(self.action, new FormData(self), headers)
                                .then(function(dataResponse) {
                                    if(dataResponse["pathname"] !== undefined && window.location.pathname === dataResponse["pathname"]) {
                                        cm.inject(dataResponse);
                                        modal.hide();
                                    } else {
                                        cm.inject(dataResponse, "." + settings.modal.container + " ." + settings.modal.body);
                                        modal.formAddListener(self.action, headers);
                                    }
                                })
                                .catch(function (message) {
                                    modal.error.show(message);
                                }).finally(function () {
                                self.style["opacity"] = null;
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
                        let error = document.querySelector("." + settings.modal.container + " ." + settings.modal.error);
                        error.innerHTML = message;
                        error.style.display = "block";
                    },
                    "hide" : function() {
                        let error = document.querySelector("." + settings.modal.container + " ." + settings.modal.error);
                        error.innerHTML = "";
                        error.style.display = "none";
                    }
                },
                "show" : function show() {
                    let modal = document.querySelector("." + settings.modal.container);
                    modal.classList.add(settings.modal.open);
                    modal.style["display"] = "block";
                },
                "hide" : function () {
                    let modal = document.querySelector("." + settings.modal.container);
                    modal.classList.remove(settings.modal.open);
                    modal.style["display"] = "none";

                    this.error.hide();
                },
                "clear" : function () {
                    document.querySelector("." + settings.modal.container + " ." + settings.modal.body).innerHTML    = "";
                    document.querySelector("." + settings.modal.container + " ." + settings.modal.error).innerHTML   = "";

                    if((title = document.querySelector("." + settings.modal.container + " ." + settings.modal.header.container + " ." + settings.modal.header.title))) {
                        title.innerHTML                             = "";
                    }
                    if((description = document.querySelector("." + settings.modal.container + " ." + settings.modal.header.container + " ." + settings.modal.header.description))) {
                        description.innerHTML                       = "";
                    }
                    if((footer = document.querySelector("." + settings.modal.container + " ." + settings.modal.footer.container))) {
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
                                cm.inject(dataResponse, "." + settings.modal.container + " ." + settings.modal.body);
                                if(dataResponse["title"] !== undefined && (title = document.querySelector("." + settings.modal.container + " ." + settings.modal.header.container + " ." + settings.modal.header.title))) {
                                    title.innerHTML = dataResponse["title"];
                                }
                                if(dataResponse["description"] !== undefined && (description = document.querySelector("." + settings.modal.container + " ." + settings.modal.header.container + " ." + settings.modal.header.description))) {
                                    description.innerHTML = dataResponse["description"];
                                }

                                if((footer = document.querySelector("." + settings.modal.container + " ." + settings.modal.footer.container))) {
                                    footer.style.display = 'none';
                                    if ((action = document.querySelector("." + settings.modal.container + " ." + settings.modal.body + " ." + settings.modal.footer.action))) {
                                        footer.appendChild(action);
                                        footer.style.display = 'block';
                                    }
                                }

                                modal.formAddListener(url, headers);
                                modal.show();

                                resolve(document.querySelector("." + settings.modal.container));
                            })
                            .catch(function (message) {
                                let error = document.querySelector("." + settings.modal.container + " ." + settings.modal.error);
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
        let links = document.querySelectorAll("a." + settings.class.modal);
        for (let i = 0; i < links.length; i++) {
            links[i].classList.remove(settings.class.modal);
            links[i].classList.add(settings.class.modal + _LOADED);
            links[i].addEventListener("click", function (e) {
                e.preventDefault();

                let self = this;
                self.style["opacity"] = "0.5";
                cm.modal.open(this.href)
                    .then(function() {
                        self.style["opacity"] = null;
                    })
                    .finally(function() {
                        self.style["opacity"] = null;
                    });
            });
        }

        links = document.querySelectorAll("a." + settings.class.xhr);
        for (let i = 0; i < links.length; i++) {
            links[i].addEventListener("click", function (e) {
                e.preventDefault();

                let self = this;
                self.style["opacity"] = "0.5";
                cm.api.get(this.href)
                    .then(function(dataResponse) {
                        cm.inject(dataResponse);
                        self.style["opacity"] = null;
                    })
                    .finally(function() {
                        self.style["opacity"] = null;
                    });
            });
        }

        links = document.querySelectorAll("form." + settings.class.xhr);
        for (let i = 0; i < links.length; i++) {
            links[i].addEventListener("submit", function (e) {
                e.preventDefault();

                let self = this;
                self.style["opacity"] = "0.5";
                cm.api.post(self.action, new FormData(self))
                    .then(function(dataResponse) {
                        cm.inject(dataResponse, self.getAttribute("data-component"));
                        self.style["opacity"] = null;
                    })
                    .catch(function(errorMessage) {
                        let error = self.querySelector("." + settings.class.error);
                        if (error) {
                            error.innerHTML = errorMessage;
                        } else {
                            let error = document.createElement("DIV");

                            error.className = settings.class.error + " " + settings.tokens.error;
                            error.innerHTML = errorMessage;

                            self.insertBefore(error, self.firstChild)
                        }
                    })
                    .finally(function() {
                        self.style["opacity"] = null;
                    });
            });
        }
    }

    document.addEventListener("DOMContentLoaded", function(event) {
        settings = {...settings, ...self.defaults};

        guiInit();
    });

    return self;
})();