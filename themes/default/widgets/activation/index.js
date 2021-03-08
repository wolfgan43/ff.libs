hcore.security.activation = function (url, redirect, resendCode, selector) {
    let selectorID = (selector
            ? "#" + selector
            : "#activation-box"
    );
    let token = $(selectorID).find("INPUT[name='csrf']").val() || "";

    if(hcore.security.identity === undefined) {
        hcore.security.identity = $(selectorID).find("INPUT[name='username']").val() || undefined;
    }

    hcore.security.initInterface(selectorID, redirect || "/login");

    if(resendCode || hcore.security.getBearerExpire() <= new Date().getTime()) {
        hcore.security.setBearer();
    }

    let headers = {
        "csrf": token
    };
    let data = {
        "identity": hcore.security.identity
    };

    $.ajax({
        url: (url || window.location.pathname),
        headers: headers,
        method: 'POST',
        dataType: 'json',
        data: data
    })
    .done(function (response) {
        if(response.status === 0) {
            if (response.data.confirm) {
                hcore.inject(response.data.confirm, selectorID);
                hcore.security.throwSuccess('Check your ' + response.data.sender);
                hcore.security.setBearer(response.data.token);
            } else if (response.data["redirect"] !== undefined) {
                hcore.security.throwSuccess('Check your ' + response.data.sender);
                hcore.security.redirect(1000, response.data.redirect);
            }
        } else {
            hcore.security.unblockAction();
            hcore.security.throwWarning(response.error);
        }

    })
    .fail(hcore.security.responseFail);

    return false;
};