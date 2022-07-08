let cm = (function () {
    const _PATHNAME             = "pathname";
    const _ALERT                = "alert";
    const _CSS                  = "css";
    const _STYLE                = "style";
    const _JS                   = "js";
    const _JS_EMBED             = "js_embed";
    const _JS_TPL               = "js_tpl";
    const _JSON_LD              = "json_ld";
    const _HTML                 = "html";
    const _COMPONENT            = "component";
    const _DEBUG                = "debug";
    const _ERROR                = "error";
    const _DATA                 = "data";
    const _LOADED               = "-loaded";
    const _LAZY                 = ".lazy";

    let cache                   = {};
    let settings                = {
        "class" : {
            "main"              : "cm-main",
            "modal"             : "cm-modal",
            "xhr"               : "cm-xhr",
            "error"             : "cm-error"
        },
        "modal" : {
            "open"              : "open",
            "container"         : "modal",
            "dialog"            : "modal-dialog",
            "header" : {
                "container"     : "modal-header",
                "close"         : "close",
                "title"         : "modal-title",
                "description"   : "modal-description"
            },
            "error"             : "modal-error",
            "body"              : "modal-body",
            "footer"            : {
                "container"     : "modal-footer",
                "action"        : "cm-action"
            },
            "tpl"				:
                '<div class="modal">' +
                '   <div class="modal-dialog">' +
                '       <div class="modal-content">' +
                '           <div class="modal-header">' +
                '               <h5 class="modal-title"></h5>' +
                '               <p class="modal-description"></p>' +
                '               <button type="button" class="close"><span>&times;</span></button>' +
                '           </div>' +
                '           <div class="modal-body"><p></p></div>' +
                '           <div class="modal-footer cm-action text-end"></div>' +
                '       </div>' +
                '   </div>' +
                '</div>',
            "tpl_bt"			: '<button id="{bt_id}" type="button" class="btn">{label}</button>',
        },
        "overlay"               : {
            "body"              : 'modal-body',
            "footer"            : {
                "container"     : "modal-footer",
                "action"        : "cm-action"
            },
            "tpl"	            :
                '<div class="modal-content">' +
                '   <div class="modal-body"></div>' +
                '   <div class="modal-footer cm-action text-end"></div>' +
                '</div>',
            "tpl_bt"	        : '<button id="{bt_id}" type="button" class="btn">{label}</button>',
        },
        "tokens" : {
            "error"             : "alert alert-danger",
            "success"           : "alert alert-success",
            "info"              : "alert alert-primary"
        }
    };

    let isLoadedResource = function (resource, type) {
        const ATTR = {
            "link"      : "href",
            "script"    : "src"
        };

        return null !== document.querySelector(type + "[" + ATTR[type] + "^='" + resource.split("?")[0] + "']");
    };

    let lazy = function() {
        const io = new IntersectionObserver((entries) =>
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    const image = entry.target;
                    image.src = image.dataset.src;
                    io.unobserve(image);
                }
            })
        );

        document.querySelectorAll(_LAZY).forEach((element) => io.observe(element));
    };

    let self = {
        "settings" : function (newsettings) {
            if(newsettings) {
                Object.assign(settings, newsettings);
            }

            return settings;
        },
        "onReady" : function (callback) {
            if (document.readyState !== 'loading') {
                callback();
            } else {
                document.addEventListener('DOMContentLoaded', function (event) {
                    callback(event);
                });
            }
        },
        "alertBox" : (function(container, selector) {
            function wrapper() {
                let errorClass = selector || settings.class.error;
                if (!container.querySelector("." + errorClass)) {
                    let error = document.createElement("DIV");
                    error.className = errorClass;
                    container.insertBefore(error, container.firstChild);
                }

                return container.querySelector("." + errorClass);
            }

            function alertBox(type, errorMessage) {
                let error = document.createElement("DIV");

                error.className = type;
                error.innerHTML = errorMessage;
                wrapper().appendChild(error);
            }

            return {
                "error" : function(errorMessage) {
                    console.error(errorMessage);
                    this.clear();
                    alertBox(settings.tokens.error, errorMessage);
                },
                "success" : function(errorMessage) {
                    console.log(errorMessage);
                    this.clear();
                    alertBox(settings.tokens.success, errorMessage);
                },
                "info" : function(errorMessage) {
                    console.info(errorMessage);
                    this.clear();
                    alertBox(settings.tokens.info, errorMessage);
                },
                "clear" : function() {
                    wrapper().innerHTML = "";
                }
            };

        }),
        "cookie" : (function() {
            return {
                "get": function (name) {
                    let v = document.cookie.match('(^|;) ?' + name + '=([^;]*)(;|$)');
                    return v ? v[2] : null;
                },
                "set": function (name, value, days) {
                    let d = new Date;
                    d.setTime(d.getTime() + 24 * 60 * 60 * 1000 * days);
                    document.cookie = name + "=" + value + ";path=/;expires=" + d.toGMTString();
                },
                "remove": function (name) {
                    this.set(name, '', -1);
                }
            };
        })(),
        "inject" : function (dataResponse, querySelector) {
            if(dataResponse === undefined) {
                return;
            }

            if (dataResponse["callback"]) {
                eval(dataResponse["callback"] + "(" + (dataResponse["params"] || undefined) + ")");
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
            if (dataResponse[_STYLE]) {
                for (let key in dataResponse[_STYLE]) {
                    if (!dataResponse[_STYLE].hasOwnProperty(key) || cache[_STYLE + dataResponse[_PATHNAME]] !== undefined) {
                        continue;
                    }
                    let style = document.createElement("style");
                    style.type = "text/css";
                    style.innerHTML = dataResponse[_STYLE][key].join("\n");
                    if(key) {
                        style.media = key;
                    }
                    document.head.appendChild(style);

                    cache[_STYLE + dataResponse[_PATHNAME]] = true;
                }
            }

            if (dataResponse[_JS_TPL] && cache[_JS_TPL + dataResponse[_PATHNAME]] === undefined) {
                document.head.insertAdjacentHTML( 'beforeend', dataResponse[_JS_TPL]);

                cache[_JS_TPL + dataResponse[_PATHNAME]] = true;
            }

            if (dataResponse[_JSON_LD] && cache[_JSON_LD + dataResponse[_PATHNAME]] === undefined) {
                let script = document.createElement("script");
                script.id = "ld" + dataResponse[_PATHNAME];
                script.type = "application/ld+json";
                script.innerHTML = dataResponse[_JSON_LD];
                document.head.appendChild(script);

                cache[_JSON_LD + dataResponse[_PATHNAME]] = true;
            }

            let html;
            let guiInit = false;
            if(dataResponse[_HTML] !== undefined) {
                if(querySelector) {
                    if((html = (document.querySelector(querySelector)))) {
                        html.innerHTML = dataResponse[_HTML].trim();

                        guiInit = true;
                    }
                } else if(dataResponse[_COMPONENT] !== undefined) {
                    if((html = (document.querySelector(dataResponse[_COMPONENT])))) {
                        html.parentNode.replaceChild(dataResponse[_HTML].trim(), html);

                        guiInit = true;
                    } else {
                        console.error("querySelector error: declare component in response or in attribute data-component.", dataResponse);
                    }
                } else {
                    let selector;
                    let div = document.createElement("div");
                    div.innerHTML = dataResponse[_HTML].trim();
                    if((selector = div.firstChild.getAttribute("id")) && (html = (document.querySelector("#" + selector)))) {
                        html.parentNode.replaceChild(div, html);

                        guiInit = true;
                    } else if((html = (document.querySelector("." + settings.class.main)))) {
                        html.innerHTML = dataResponse[_HTML].trim();

                        guiInit = true;
                    } else {
                        console.error("querySelector error: missing class ." + settings.class.main + " in template ", dataResponse);
                    }
                }
            }

            if ((typeof dataResponse[_JS] === "object")
                || (dataResponse[_JS_EMBED] && cache[_JS_EMBED + dataResponse[_PATHNAME]] === undefined)) {

                (function loadNextScript () {
                    if (typeof dataResponse[_JS] === "object") {
                        for (let key in dataResponse[_JS]) {
                            if (!dataResponse[_JS].hasOwnProperty(key) || isLoadedResource(key, "script")) {
                                continue;
                            }

                            let script = document.createElement("script");
                            script.defer = true;
                            script.type = "application/javascript";
                            script.onload = loadNextScript;
                            script.src = key;
                            document.head.appendChild(script);
                            delete dataResponse[_JS][key];
                            return;
                        }
                    }

                    if (dataResponse[_JS_EMBED] && cache[_JS_EMBED + dataResponse[_PATHNAME]] === undefined) {
                        let script = document.createElement("script");
                        script.type = "application/javascript";
                        script.innerHTML = dataResponse[_JS_EMBED];
                        document.head.appendChild(script);

                        cache[_JS_EMBED + dataResponse[_PATHNAME]] = true;
                    }

                    guiInit && self.guiInit();
                })();
            } else {
                guiInit && self.guiInit();
            }
        },
        "guiInit" : function (domElement) {
            domElement = domElement || document;

            let links = domElement.querySelectorAll("a." + settings.class.modal);
            for (let i = 0; i < links.length; i++) {
                links[i].classList.remove(settings.class.modal);
                links[i].classList.add(settings.class.modal + _LOADED);
                links[i].addEventListener("click", function (e) {
                    e.preventDefault();

                    let tmp;
                    let options = {
                        "events" : {
                        },
                    };
                    if ((tmp = this.getAttribute("modal-open"))) {
                        options.events.open = eval.bind(null, tmp);
                    }
                    if ((tmp = this.getAttribute("modal-close"))) {
                        options.events.close = eval.bind(null, tmp);
                    }

                    cm.modal.open(this.href, {}, "get", undefined, options);
                });
            }

            links = domElement.querySelectorAll("a." + settings.class.xhr);
            for (let i = 0; i < links.length; i++) {
                links[i].addEventListener("click", function (e) {
                    e.preventDefault();

                    let self = this;
                    cm.alertBox(self.parentNode).clear();
                    self.style["opacity"] = "0.8";
                    cm.api.get(this.href)
                        .then(function(dataResponse) {
                            cm.inject(dataResponse);
                            if (dataResponse[_ALERT]) {
                                cm.alertBox(self.parentNode).success(dataResponse[_ALERT]);
                            }
                        })
                        .catch(function(errorMessage) {
                            cm.alertBox(self.parentNode).error(errorMessage);
                        })
                        .finally(function() {
                            self.style["opacity"] = null;
                        });
                });
            }

            links = domElement.querySelectorAll("form." + settings.class.xhr);
            for (let i = 0; i < links.length; i++) {
                links[i].addEventListener("submit", function (e) {
                    e.preventDefault();

                    let self = this;
                    cm.alertBox(self).clear();
                    self.style["opacity"] = "0.8";
                    cm.api.post(self.action, new FormData(self))
                        .then(function(dataResponse) {
                            cm.inject(dataResponse, self.getAttribute("data-component"));
                            if (dataResponse[_ALERT]) {
                                cm.alertBox(self).success(dataResponse[_ALERT]);
                            }
                        })
                        .catch(function(errorMessage) {
                            cm.alertBox(self).error(errorMessage);
                        })
                        .finally(function() {
                            self.style["opacity"] = null;
                        });
                });
            }
        },
        "api" : (function () {
            let xhr = new XMLHttpRequest();

            function request(method, url, headers, data, returnRawData) {
                return new Promise(function (resolve, reject) {
                    xhr.open(method, url);
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    for (let key in headers) {
                        if (headers.hasOwnProperty(key)) {
                            xhr.setRequestHeader(key, headers[key]);
                        }
                    }
                    xhr.onreadystatechange = function () {
                        if (xhr.readyState === XMLHttpRequest.DONE) {
                            let resp;
                            if ((resp = xhr.responseText)) {
                                let respJson = JSON.parse(resp);

                                if (respJson[_DEBUG] !== undefined) {
                                    console.log(method + " " + url, data, headers, respJson[_DEBUG]);
                                }

                                if (xhr.status === 200) {
                                    if (respJson["redirect"] !== undefined) {
                                        window.location.href = respJson["redirect"];
                                    } else {
                                        resolve(returnRawData ? respJson : respJson[_DATA]);
                                    }
                                } else {
                                    reject(respJson[_ERROR]);
                                }
                            } else if (xhr.status === 204) {
                                resolve(returnRawData ? undefined : []);
                            } else {
                                reject("xhr response empty");
                            }
                        }
                    }

                    xhr.send(typeof data !== 'object' || data instanceof FormData ? data : JSON.stringify(data));
                });
            }

            function responseCSRF(method, url, headers, data) {
                let csrf;
                if ((csrf = cm.cookie.get("csrf"))) {
                    headers = {...headers, ...{"X-CSRF-TOKEN": csrf}};
                }
                return request(method, url, headers, data);
            }

            return {
                "get": function (url, headers) {
                    return request("GET", url, headers);
                },
                "post": function (url, data, headers) {
                    return responseCSRF("POST", url, headers, data);
                },
                "put": function (url, headers) {
                    return responseCSRF("PUT", url, headers, data);
                },
                "patch": function (url, headers) {
                    return responseCSRF("PATCH", url, headers, data);
                },
                "delete": function (url, headers) {
                    return responseCSRF("DELETE", url, headers, data);
                },
                "search": function (url, data, headers) {
                    return request("POST", url, headers, data, true);
                },
            };
        })(),
        "modal" : (function() {
            let privates = {
                "$container": undefined,
                "events": undefined,
            };

            let publics = {
                "overlay" : (function() {
                    let ov_privates = {
                        $node : undefined,
                        events: undefined
                    };
                    let ov_publics = {
                        "create": function({
                                               message,
                                               buttons,
                                               nodefaultbt = false,
                                               defaultaction,
                                               show = true, // false to open it later after open() ajax call
                                               events = {
                                                   "open"	: undefined,
                                                   "close" : undefined,
                                               }
                                           } = {}) {
                            return new Promise((resolve, reject) => {
                                if (!(body = privates.$container && privates.$container.querySelector("." + settings.modal.body))) {
                                    return reject("dialog not opened");
                                }
                                if (ov_privates.$node) {
                                    resolve('created');
                                    return;
                                }

                                ov_privates.events = events;

                                ov_privates.$node = document.createElement("cm-modal-ovl");
                                ov_privates.$node.innerHTML = settings.overlay.tpl;
                                ov_privates.$node = ov_privates.$node.firstElementChild;
                                ov_privates.$node.prepend(privates.$container.querySelector("." + settings.modal.header.container).cloneNode(true));

                                if (!nodefaultbt && (!buttons || !buttons.length)) {
                                    buttons = [];
                                    buttons.push({
                                        "id"		: "continue",
                                        "label"		: "Continua",
                                        "class"		: "btn-primary",
                                        "action"	: defaultaction,
                                        "hide"		: true,
                                    });
                                }

                                ov_publics.set({
                                    message,
                                    buttons,
                                });

                                privates.$container.querySelector("." + settings.modal.dialog).appendChild(ov_privates.$node);
                                if (show) {
                                    // code to show
                                }

                                resolve('created');
                            });
                        },
                        "bt" : function (button) {
                            if (button.hide) {
                                ov_publics.close();
                            }

                            if (button.action !== undefined) {
                                button.action();
                            }
                        },
                        "set" : function ({
                                              message,
                                              buttons,
                                              actions = ""
                                          } = {}) {

                            if (message !== undefined) {
                                let $message = ov_privates.$node.querySelector("." + settings.overlay.body);
                                if ($message) {
                                    $message.innerHTML = message;
                                }
                            }

                            let display_footer = false;
                            let $footer = ov_privates.$node.querySelector("." + settings.overlay.footer.container);
                            if (actions !== undefined) {
                                $footer.innerHTML = "";
                            }

                            if (buttons !== undefined) {
                                display_footer = true;
                                buttons.forEach(function (v) {
                                    let tmp = settings.overlay.tpl_bt.replaceAll("{bt_id}", v.id).replaceAll("{label}", v.label);
                                    let $bt = document.createElement("dialog_bt");
                                    $bt.innerHTML = tmp;
                                    $bt = $bt.firstElementChild;
                                    if (!v.id) {
                                        $bt.removeAttribute("id");
                                    }
                                    if (v.class) {
                                        $bt.classList.add(v.class);
                                    }
                                    $bt.addEventListener("click", ov_publics.bt.bind(null, v));
                                    ov_privates.$node.querySelector("." + settings.overlay.footer.action).appendChild($bt);
                                });
                            }

                            if (buttons === undefined) { // check for actions embedded in the content
                                let $tmp;
                                if (($tmp = ov_privates.$node.querySelector("." + settings.overlay.body + " ." + settings.overlay.footer.action))) {
                                    display_footer = true;
                                    $footer.appendChild($tmp);
                                }
                            }

                            $footer.style.display = display_footer ? 'block' : 'none';
                        },
                        "open" : function() {
                            // to be used for ajax call
                            // it will show it when done
                        },
                        "close" : function() {
                            if (!ov_privates.$node) {
                                return;
                            }

                            if (ov_privates.events.close) {
                                ov_privates.events.close();
                            }
                            ov_privates.$node.remove();
                            ov_privates.$node	    = undefined;
                            ov_privates.events		= undefined;
                        },
                    };
                    return ov_publics;
                })(),
                "create" : function ({
                                         title,
                                         description,
                                         message,
                                         buttons,
                                         nodefaultbt = false,
                                         defaultaction,
                                         show = true,
                                         events = {
                                             "open"	: undefined,
                                             "close" : undefined,
                                         },
                                     } = {}) {
                    return new Promise(resolve => {
                        self.onReady(function () {
                            if (privates.$container) {
                                resolve('created');
                                return;
                            }

                            privates.events = events;

                            privates.$container = document.createElement("cm-modal-dlg");
                            privates.$container.innerHTML = settings.modal.tpl;
                            privates.$container = privates.$container.firstElementChild;

                            let close;
                            if ((close = privates.$container.querySelector("." + settings.modal.header.close))) {
                                close.addEventListener("click", function () {
                                    publics.close();
                                });
                            }

                            if (!nodefaultbt && (!buttons || !buttons.length) && !close) {
                                buttons = [];
                                buttons.push({
                                    "id"		: "continue",
                                    "label"		: "Continua",
                                    "class"		: "btn-primary",
                                    "action"	: defaultaction,
                                    "hide"		: true,
                                });
                            }

                            publics.set({
                                title,
                                description,
                                message,
                                buttons,
                            });

                            document.getElementsByTagName("body")[0].appendChild(privates.$container);
                            if (show) {
                                modal.show();
                            }

                            resolve('created');
                        });
                    });
                },
                "bt" : function (button) {
                    if (button.hide) {
                        publics.close();
                    }

                    if (button.action !== undefined) {
                        button.action();
                    }
                },
                "set" : function ({
                                      title,
                                      description,
                                      message,
                                      buttons,
                                      actions = ""
                                  } = {}) {
                    if (title !== undefined) {
                        let $title = privates.$container.querySelector("." + settings.modal.header.title)
                        if ($title) {
                            $title.innerHTML = title;
                        }
                    }

                    if (description !== undefined) {
                        let $description = privates.$container.querySelector("." + settings.modal.header.description)
                        if ($description) {
                            $description.innerHTML = description;
                        }
                    }

                    if (message !== undefined) {
                        let $message = privates.$container.querySelector("." + settings.modal.body);
                        if ($message) {
                            $message.innerHTML = message;
                        }
                    }

                    let display_footer = false;
                    let $footer = privates.$container.querySelector("." + settings.modal.footer.container);
                    if (actions !== undefined) {
                        $footer.innerHTML = "";
                    }

                    if (buttons !== undefined) {
                        display_footer = true;
                        buttons.forEach(function (v) {
                            let tmp = settings.modal.tpl_bt.replaceAll("{bt_id}", v.id).replaceAll("{label}", v.label);
                            let $bt = document.createElement("dialog_bt");
                            $bt.innerHTML = tmp;
                            $bt = $bt.firstElementChild;
                            if (!v.id) {
                                $bt.removeAttribute("id");
                            }
                            if (v.class) {
                                $bt.classList.add(v.class);
                            }
                            $bt.addEventListener("click", publics.bt.bind(null, v));
                            privates.$container.querySelector("." + settings.modal.footer.action).appendChild($bt);
                        });
                    }

                    if (buttons === undefined) { // check for actions embedded in the content
                        let $tmp;
                        if (($tmp = privates.$container.querySelector("." + settings.modal.body + " ." + settings.modal.footer.action))) {
                            display_footer = true;
                            $footer.appendChild($tmp);
                        }
                    }

                    $footer.style.display = display_footer ? 'block' : 'none';
                },
                "open" : function(url, headers = {}, method = "get", formdata = undefined, options = {}) {
                    return new Promise(function (resolve, reject) {
                        if (privates.$container) {
                            modal.error.hide();
                            privates.$container.style["opacity"] = "0.8";
                        }
                        cm.api[method](url, method === "get" ? headers : formdata, method === "post" ? headers : undefined)
                            .then(function (dataResponse) {
                                if (dataResponse["pathname"] !== undefined && window.location.pathname.replace(/\/$/, '') === dataResponse["pathname"]) {
                                    cm.inject(dataResponse);
                                    publics.close();
                                    return;
                                }

                                publics.create(Object.assign(options, {"show" : false})).then(_ => {
                                    cm.inject(dataResponse, "." + settings.modal.container + " ." + settings.modal.body);
                                    if (dataResponse["close"]) {
                                        modal.hide();
                                        return;
                                    }

                                    publics.set({
                                        title : dataResponse["title"],
                                        description : dataResponse["description"],
                                    });

                                    modal.formAddListener(url, headers);
                                    modal.show();

                                    resolve(privates.$container);
                                });
                            })
                            .catch(function (errorMessage) {
                                publics.create({"show" : false}).then(_ => {
                                    modal.error.show(errorMessage);
                                    modal.show();

                                    reject(errorMessage);
                                });
                            }).finally(function () {
                            if (privates.$container) {
                                privates.$container.style["opacity"] = null;
                            }
                        });
                    });
                },
                "close" : function() {
                    if (!privates.$container) {
                        return;
                    }
                    modal.hide();
                    publics.overlay.close();
                    if (privates.events.close) {
                        privates.events.close();
                    }
                    privates.$container.remove();
                    privates.$container	= undefined;
                    privates.events		= undefined;
                },
            };

            let modal = {
                "formAddListener" : function(url, headers = {}) {
                    let $form;
                    if (($form = privates.$container.querySelector("form"))) {
                        $form.action = url;
                        $form.addEventListener("submit", function (e) {
                            e.preventDefault();

                            publics.open(url, headers, "post", new FormData($form));
                        });
                    }
                },
                "error" : {
                    "show" : function(message) {
                        if (!privates.$container) {
                            return;
                        }

                        cm.alertBox(privates.$container.querySelector("." + settings.modal.body), settings.modal.error).error(message);
                    },
                    "hide" : function() {
                        if (!privates.$container) {
                            return;
                        }
                        cm.alertBox(privates.$container.querySelector("." + settings.modal.body), settings.modal.error).clear();
                    }
                },
                "show" : function show() {
                    if (!privates.$container) {
                        throw new Error("cm - dialog not created");
                    }

                    privates.$container.classList.add(settings.modal.open);
                    privates.$container.style["display"] = "block";

                    if (privates.events.open) {
                        privates.events.open({
                            "$dialog"	: privates.$container,
                        });
                    }
                },
                "hide" : function () {
                    if (!privates.$container) {
                        return;
                    }

                    privates.$container.classList.remove(settings.modal.open);
                    privates.$container.style["display"] = "none";

                    this.error.hide();
                },
                "clear" : function () {
                    modal.error.hide();
                    publics.set({
                        title : "",
                        description : "",
                        message : "",
                    });
                }
            }

            return publics;
        })()
    }

    self.onReady(function() {
        self.guiInit();
        lazy();
    });

    return self;
})();