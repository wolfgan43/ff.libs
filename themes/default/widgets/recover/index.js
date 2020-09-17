hcore.Auth.recover = function (url, redirect, selector, resendCode) {
    let selectorID = (selector
            ? "#" + selector
            : "#recover-box"
    );

    if(hcore.Auth.identity === undefined) {
        hcore.Auth.identity = $(selectorID).find("INPUT[name='username']").val() || undefined;
    }
    let domain = $(selectorID).find("INPUT[name='domain']").val() || undefined;
    let password = $(selectorID).find("INPUT[name='password']").val() || undefined;
    let token = $(selectorID).find("INPUT[name='csrf']").val() || "";

    hcore.Auth.initInterface(selectorID, redirect || "/user");

    if(resendCode || hcore.Auth.getBearerExpire() <= new Date().getTime()) {
        $(selectorID).find("INPUT[name='codice-conferma']").val('');
        //$(selectorID).find("INPUT[name='password']").val('');
        //$(selectorID).find("INPUT[name='confirm-password']").val('');

        hcore.Auth.setBearer();
    }

    let bearer = hcore.Auth.getBearer();
    let headers = {};
    let data = {};

    if (bearer) {
        headers = {
            "Bearer": bearer
            , "domain": domain
            , "csrf": token
        };
        data = {
            "key": password,
            "redirect": redirect,
        };
    } else {
        headers = {
            "domain": domain,
            "csrf": token
        };
        data = {
            "identity": hcore.Auth.identity
        };
    }

    $.ajax({
        url: (url || window.location.pathname),
        headers: headers,
        method: 'POST',
        dataType: 'medreq.json',
        data: data

    })
    .done(function (response) {
        if (response.status === 0) {
            if (response.data.recover_confirm) {
                hcore.inject(response.data.recover_confirm, selectorID);
                hcore.Auth.throwSuccess('Check your ' + response.data.sender);
                hcore.Auth.setBearer(response.data.bearer);
            } else if (response.data["redirect"] !== undefined) {
                hcore.Auth.throwSuccess('Check your ' + response.data.sender);
                hcore.Auth.redirect(1000, response.data.redirect);
            }
        } else {
            hcore.Auth.unblockAction();
            hcore.Auth.throwException(response.error)
        }
    })
    .fail(hcore.Auth.responseFail);

    return false;
};

hcore.Auth.recover_confirm = function (url, redirect, selector) {
    let selectorID = (selector
            ? "#" + selector
            : "#recover-box"
    );

    let domain = $(selectorID).find("INPUT[name='domain']").val() || undefined;
    let token = $(selectorID).find("INPUT[name='csrf']").val() || "";
    let password = $(selectorID).find("INPUT[name='password']").val() || "";
    let confirmPassword = $(selectorID).find("INPUT[name='confirm-password']").val() || "";
    let verifyCode = $(selectorID).find("INPUT[name='codice-conferma']").val() || undefined;

    hcore.Auth.initInterface(selectorID, redirect);

    if(hcore.Auth.getBearerExpire() <= new Date().getTime()) {
        hcore.Auth.setBearer();
    }

    let bearer = hcore.Auth.getBearer();
    let headers = {};
    let data = {};

    if (bearer) {
        if(confirmPassword !== password) {
            hcore.Auth.throwWarning('I campi "password" e "conferma password" non coincidono');
            return false;
        }

        headers = {
            "Bearer": bearer
            , "domain": domain
            , "csrf": token
        };
        data = {
            "code": $.trim(verifyCode),
            "value": password,
            "redirect": redirect
        };

        $.ajax({
            url: (url || window.location.pathname),
            headers: headers,
            method: 'POST',
            dataType: 'medreq.json',
            data: data
        })
        .done(function (response) {
            if (response.status === 0) {
                hcore.Auth.throwSuccess('Operation completed successfully!');
                hcore.Auth.redirect(1000);
            } else {
                hcore.Auth.unblockAction();
                hcore.Auth.throwWarning(response.error);
            }
        })
        .fail(hcore.Auth.responseFail);
    } else {
        hcore.Auth.throwException("Si è verificato un errore")
    }

    return false;
};