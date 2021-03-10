hcore.security.login = function (url, redirect, selector) {
    let selectorID = (selector
            ? "#" + selector
            : "#login-box"
    );
    let token       = $(selectorID).find("INPUT[name='csrf']").val() || "";

    let username    = $(selectorID).find("INPUT[name='username']").val() || undefined;
    let password    = $(selectorID).find("INPUT[name='password']").val() || undefined;
    let permanent   = $(selectorID).find("INPUT[name='stayconnected']").is(':checked') || false;

    hcore.security.identifier = $(selectorID).find("INPUT[name='username']").val() || hcore.security.identifier || undefined;
    hcore.security.initInterface(selectorID, redirect || "/");

    let headers = {
        "csrf"          : token
    };
    let data = {
        "identifier"    : username,
        "password"      : password,
        "permanent"     : permanent ? 1 : 0
    };

    $.ajax({
        url: (url || window.location.pathname),
        headers: headers,
        method: "POST",
        dataType: "json",
        data: data
    })
    .done(function (response) {
        if (response.status === 0) {
            if (response.data.welcome) {
                hcore.inject(response.data.welcome, selectorID);
            }

            hcore.security.redirect(1000);
        } else {
            hcore.security.unblockAction();
            hcore.security.throwWarning(response.error_link
                ? '<a href="' + response.error_link + '">' + response.error + '</a>'
                : response.error
            );
        }
    })
    .fail(hcore.security.responseFail);

    return false;
};