hcore.Auth.activation = function (url, redirect, selector, resendCode) {
    let selectorID = (selector
            ? "#" + selector
            : "#activation-box"
    );

    let domain = $(selectorID).find("INPUT[name='domain']").val() || "";
    let identity = $(selectorID).find("INPUT[name='username']").val() || "";
    let token = $(selectorID).find("INPUT[name='csrf']").val() || "";
    let verifyCode = $(selectorID).find("INPUT[name='codice-conferma']").val() || "";

    hcore.Auth.initInterface(selectorID, redirect);

    if(resendCode || hcore.Auth.getBearerExpire() <= new Date().getTime()) {
        hcore.Auth.setBearer();
    }

    let bearer = hcore.Auth.getBearer();
    let headers = {};
    let data = {};

    if(bearer) {
        headers = {
            "Bearer" : bearer,
            "domain": domain,
            "csrf": token
        };
        data = {
            "code": $.trim(verifyCode),
            "identity": identity
            //, "key" : value
        };
    } else {
        headers = {
            "domain": domain,
            "csrf": token
        };
        data = {
            "identity": identity
        };
    }

    $.ajax({
        url: (url || window.location.pathname),
        headers: headers,
        method: 'POST',
        dataType: 'json',
        data: data

    })
    .done(function (response) {
        if(response.status === 0) {
            if(bearer) {
                hcore.Auth.throwSuccess('Your Account has been Activated!');
                hcore.Auth.redirect(1000, redirect)
            } else {
                hcore.Auth.unblockAction();
                hcore.Auth.setBearer(response.data.bearer);
                if(!response.data.sender) {
                    response.data.sender = "email";
                }

                if(response.data.sender) {
                    hcore.Auth.throwSuccess('Check your ' + response.data.sender);

                    $(selectorID).find("INPUT[name='username']").prop('disabled', true);
                    $(selectorID + " .verify-code").removeClass("d-none");
                }
            }
        } else {
            hcore.Auth.unblockAction();
            hcore.Auth.throwWarning(response.error);
        }

    })
    .fail(hcore.Auth.responseFail);


    return false;
};