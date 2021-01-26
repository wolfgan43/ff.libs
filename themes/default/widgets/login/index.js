hcore.security.login = function (url, redirect, selector) {
    let selectorID = (selector
            ? "#" + selector
            : "#login-box"
    );

    let domain = $(selectorID).find("INPUT[name='domain']").val() || window.location.host;
    let username = $(selectorID).find("INPUT[name='username']").val() || undefined;
    let password = $(selectorID).find("INPUT[name='password']").val() || undefined;
    let csrf = $(selectorID).find("INPUT[name='csrf']").val() || "";
    let stayConnect = $(selectorID).find("INPUT[name='stayconnected-new']").is(':checked') || false;

    hcore.security.initInterface(selectorID, redirect);

    $.ajax({
        url: (url || window.location.pathname),
        headers: {
            "domain": domain,
            "csrf": csrf,
            "refresh": stayConnect
        },
        method: "POST",
        dataType: "json",
        data: {
            "username": username,
            "password": password
        }
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