let cm = (function () {
    const _PATHNAME             = "pathname";
    const _CSS                  = "css";
    const _STYLE                = "style";
    const _FONTS                = "fonts";
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

    let cache                   = {};
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
            },
			"tpl"				: '<div class="cm-modal cm-open" style="">    <div class="cm-modal-dialog cm-overflow-hidden">        <div class="cm-modal-header">            <h2 class="cm-modal-title white-text cm-margin-remove"></h2>            <p class="cm-modal-description white-text cm-margin-remove"></p>            <button class="cm-modal-close-default white-text cm-icon cm-close" type="button"><svg width="14" height="14" viewBox="0 0 14 14" xmlns="http://www.w3.org/2000/svg" data-svg="close-icon"><line fill="none" stroke="#000" stroke-width="1.1" x1="1" y1="1" x2="13" y2="13"></line><line fill="none" stroke="#000" stroke-width="1.1" x1="13" y1="1" x2="1" y2="13"></line></svg></button>        </div>        <div class="cm-modal-body">        </div>        <div class="cm-modal-footer cm-action">        </div>    </div></div>',
			"tpl_bt"			: '<button id="{bt_id}" type="button" class="btn">{label}</button>',
			
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
	
	function onReady(cb) {
		if (document.readyState !== 'loading') {
			cb();
		} else {
			document.addEventListener('DOMContentLoaded', function (event) {
				cb(event);
			});
		}
	}

    let self = {
        "settings" : settings,
        "error" : (function(container, selector) {
            function wrapper() {
                let errorClass = selector || settings.class.error;
                if (!container.querySelector("." + errorClass)) {
                    let error = document.createElement("DIV");
                    error.className = errorClass;
                    container.insertBefore(error, container.firstChild);
                }

                return container.querySelector("." + errorClass);
            }

            return {
                "set" : function(errorMessage) {
                    console.error(errorMessage);

                    this.clear();
                    let error = document.createElement("DIV");

                    error.className = settings.tokens.error;
                    error.innerHTML = errorMessage;
                    wrapper().appendChild(error);
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
            if (dataResponse[_JS_EMBED] && cache[_JS_EMBED + dataResponse[_PATHNAME]] === undefined) {
                let script = document.createElement("script");
                script.type = "application/javascript";
                script.innerHTML = dataResponse[_JS_EMBED];
                document.head.appendChild(script);

                cache[_JS_EMBED + dataResponse[_PATHNAME]] = true;
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
                            if((resp = xhr.responseText)) {
                                let respJson = JSON.parse(resp);

                                if (respJson[_DEBUG] !== undefined) {
                                    console.log(method + " " + url, data, headers, respJson[_DEBUG]);
                                }

                                if (xhr.status === 200) {
                                    if(respJson["redirect"] !== undefined) {
                                        window.location.href = respJson["redirect"];
                                    } else {
                                        resolve(returnRawData ? respJson : respJson[_DATA]);
                                    }
                                } else {
                                    reject(respJson[_ERROR]);
                                }
                            } else if(xhr.status === 204) {
                                resolve(returnRawData ? undefined : []);
                            } else {
                                reject("xhr response empty");
                            }
                        } else {
                        }
                    }
                    xhr.send(typeof data !== 'object' || data instanceof FormData ? data : JSON.stringify(data));
                });
            }

            function responseCSRF(method, url, headers, data) {
                if((csrf = cm.cookie.get("csrf"))) {
                    headers = {...headers, ...{"X-CSRF-TOKEN" : csrf}};
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
			var $dialog = undefined;
			
			function getTplObj(domObj, selectorClass) {
				let tmp = domObj.querySelector("." + selectorClass);
				tmp.classList.remove(selectorClass);
				let tpl = tmp.outerHTML;
				tmp.remove();
				return tpl;
			}
			
            let modal = {
                "formAddListener" : function(url, headers = {}) {
                    if(($form = $dialog.querySelector("form"))) {
                        $form.action = url;
                        $form.addEventListener("submit", function(e) {
                            e.preventDefault();

                            publics.open(url, headers, "post", new FormData($form));
                        });
                    }
                },
				
                "error" : {
                    "show" : function(message) {
						if (!$dialog) {
							return;
						}

						cm.error($dialog.querySelector("." + settings.modal.body), settings.modal.error).set(message);
                    },
                    "hide" : function() {
						if (!$dialog) {
							return;
						}
                        cm.error($dialog.querySelector("." + settings.modal.body), settings.modal.error).clear();
                    }
                },
                "show" : function show() {
					if (!$dialog) {
						throw new Error("cm - dialog not created");
					}
					
                    $dialog.classList.add(settings.modal.open);
                    $dialog.style["display"] = "block";
                },
                "hide" : function () {
					if (!$dialog) {
						return;
					}
					
                    $dialog.classList.remove(settings.modal.open);
                    $dialog.style["display"] = "none";

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

            var publics = {
				"create" : function ({
					title,
					description,
					message,
					buttons,
					nodefaultbt = false,
					defaultaction,
					show = true,
				} = {}) {
					return new Promise(resolve => {
						onReady(function (event){
							if ($dialog) {
								resolve('created');
								return;
							}
							
							$dialog = document.createElement("cm-modal-dlg");
							$dialog.innerHTML = settings.modal.tpl;
							$dialog = $dialog.firstElementChild;

							let close;
							if (close = $dialog.querySelector("." + settings.modal.header.close)) {
								close.addEventListener("click", function () {
									publics.close();
								});
							};

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

							document.getElementsByTagName("body")[0].appendChild($dialog);
							if (show) {
								modal.show();
							}
							
							resolve('created');
						}); // onReady
					}); // promise
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
						let $title = $dialog.querySelector("." + settings.modal.header.title)
						if ($title) {
							$title.innerHTML = title;
						}
					}
					
					if (description !== undefined) {
						let $description = $dialog.querySelector("." + settings.modal.header.description)
						if ($description) {
							$description.innerHTML = description;
						}
					}

					if (message !== undefined) {
						let $message = $dialog.querySelector("." + settings.modal.body);
						if ($message) {
							$message.innerHTML = message;
						}
					}
					
					let display_footer = false;
					let $footer = $dialog.querySelector("." + settings.modal.footer.container);
					if (actions !== undefined) {
						$footer.innerHTML = "";
					}

					if (buttons !== undefined) {
						display_footer = true;
						buttons.each(function (i, v) {
							var tmp = settings.modal.tpl_bt.replaceAll("{bt_id}", v.id);
							tmp = tmp.replaceAll("{label}", v.label);
							var $bt = document.createElement("dialog_bt");
							$bt.innerHTML = tmp;
							$bt = $bt.firstElementChild;
							if (!v.id) {
								$bt.removeAttribute("id");
							}
							if (v.class) {
								$bt.classList.add(v.class);
							}
							$bt.addEventListener("click", publics.bt.bind(null, v));
							$dialog.querySelector("." + settings.modal.footer.action).appendChild($bt);
							/*$dialog.querySelector("." + settings.modal.footer.action).appendChild(...$bt.childNodes);
							$bt.remove();*/
						});
					}
					
					if (buttons === undefined) { // check for actions embedded in the content
						if (($tmp = $dialog.querySelector("." + settings.modal.body + " ." + settings.modal.footer.action))) {
							display_footer = true;
							$footer.appendChild($tmp);
						}
					}
					
					$footer.style.display = display_footer ? 'block' : 'none';
				},
				
                "open" : function(url, headers = {}, method = "get", formdata = undefined) {
                    return new Promise(function (resolve, reject) {
                        if ($dialog) {
                            modal.error.hide();
							$dialog.style["opacity"] = "0.8";
						}
                        cm.api[method](url, method === "get" ? headers : formdata, method === "post" ? headers : undefined)
                            .then(function (dataResponse) {
								if(dataResponse["pathname"] !== undefined && window.location.pathname === dataResponse["pathname"]) {
									cm.inject(dataResponse);
									publics.close();
									return;
								}
									
								publics.create({"show" : false}).then(_ => {							
									cm.inject(dataResponse, "." + settings.modal.container + " ." + settings.modal.body);

									publics.set({
										title : dataResponse["title"],
										description : dataResponse["description"],
									});

									modal.formAddListener(url, headers);
									modal.show();

									resolve($dialog);
								});
                            })
                            .catch(function (errorMessage) {
								publics.create({"show" : false}).then(_ => {							
									modal.error.show(errorMessage);
									modal.show();

	                                reject(errorMessage);
								});
							}).finally(function () {
								if ($dialog) {
									$dialog.style["opacity"] = null;
								}
                            });
                    });
                },
                "close" : function() {
					if (!$dialog) {
						return;
					}
                    modal.hide();
					$dialog.remove();
					$dialog = undefined;
                }
            };
			return publics;
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
                self.style["opacity"] = "0.8";
                cm.modal.open(this.href)
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
                self.style["opacity"] = "0.8";
                cm.api.get(this.href)
                    .then(function(dataResponse) {
                        cm.inject(dataResponse);
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
                self.style["opacity"] = "0.8";
                cm.api.post(self.action, new FormData(self))
                    .then(function(dataResponse) {
                        cm.inject(dataResponse, self.getAttribute("data-component"));
                    })
                    .catch(function(errorMessage) {
                        cm.error(self).set(errorMessage);
                    })
                    .finally(function() {
                        self.style["opacity"] = null;
                    });
            });
        }
    }

    document.addEventListener("DOMContentLoaded", function() {
        Object.assign(settings, self.settings);

        guiInit();
    });

    return self;
})();