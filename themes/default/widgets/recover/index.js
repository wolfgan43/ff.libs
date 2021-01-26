hcore.security.recover = function (url, redirect, selector, resendCode) {
    let selectorID = (selector
            ? "#" + selector
            : "#recover-box"
    );

    if(hcore.security.identity === undefined) {
        hcore.security.identity = $(selectorID).find("INPUT[name='username']").val() || undefined;
    }
    let domain = $(selectorID).find("INPUT[name='domain']").val() || undefined;
    let password = $(selectorID).find("INPUT[name='password']").val() || undefined;
    let token = $(selectorID).find("INPUT[name='csrf']").val() || "";

    hcore.security.initInterface(selectorID, redirect || "/user");

    if(resendCode || hcore.security.getBearerExpire() <= new Date().getTime()) {
        $(selectorID).find("INPUT[name='codice-conferma']").val('');
        //$(selectorID).find("INPUT[name='password']").val('');
        //$(selectorID).find("INPUT[name='confirm-password']").val('');

        hcore.security.setBearer();
    }

    let bearer = hcore.security.getBearer();
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
            "identity": hcore.security.identity
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
        if (response.status === 0) {
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
            hcore.security.throwException(response.error)
        }
    })
    .fail(hcore.security.responseFail);

    return false;
};

hcore.security.recoverConfirm = function (url, redirect, selector) {
    let selectorID = (selector
            ? "#" + selector
            : "#recover-box"
    );

    let domain = $(selectorID).find("INPUT[name='domain']").val() || undefined;
    let token = $(selectorID).find("INPUT[name='csrf']").val() || "";
    let password = $(selectorID).find("INPUT[name='password']").val() || "";
    let confirmPassword = $(selectorID).find("INPUT[name='confirm-password']").val() || "";
    let verifyCode = $(selectorID).find("INPUT[name='codice-conferma']").val() || undefined;

    hcore.security.initInterface(selectorID, redirect);

    if(hcore.security.getBearerExpire() <= new Date().getTime()) {
        hcore.security.setBearer();
    }

    let bearer = hcore.security.getBearer();
    let headers = {};
    let data = {};

    if (bearer) {
        if(confirmPassword !== password) {
            hcore.security.unblockAction();
            hcore.security.throwWarning('I campi "password" e "conferma password" non coincidono');
            return false;
        }

        headers = {
            "Authorization": bearer,
            "csrf": token
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
            dataType: 'json',
            data: data
        })
        .done(function (response) {
            if (response.status === 0) {
                hcore.security.throwSuccess('Operation completed successfully!');
                hcore.security.redirect(1000);
            } else {
                hcore.security.unblockAction();
                hcore.security.throwWarning(response.error);
            }
        })
        .fail(hcore.security.responseFail);
    } else {
        hcore.security.throwException("Si è verificato un errore")
    }

    return false;
};