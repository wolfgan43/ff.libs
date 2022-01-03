hcore.security.activation = function (url, redirect, resendCode, selector) {
    let selectorID = (selector
            ? "#" + selector
            : "#activation-box"
    );

    hcore.security.identifier = $(selectorID).find("INPUT[name='username']").val() || hcore.security.identifier || undefined;
    hcore.security.initInterface(selectorID, redirect || "/login");

    if(resendCode || hcore.security.getBearerExpire() <= new Date().getTime()) {
        hcore.security.setBearer();
    }

    let headers = {
    };
    let data = {
        "identifier": hcore.security.identifier
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
            } else if (response.data.redirect !== undefined) {
                hcore.security.throwSuccess('Check your ' + response.data.sender);
                hcore.security.redirect(7000, response.data.redirect);
            }
        } else {
            hcore.security.unblockAction();
            hcore.security.throwWarning(response.error);
        }

    })
    .fail(hcore.security.responseFail);

    return false;
};