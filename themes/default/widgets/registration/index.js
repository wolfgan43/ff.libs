hcore.security.registration = function (url, redirect, selector) {
    let selectorID = (selector
            ? "#" + selector
            : "#registration-box"
    );

    let domain = $(selectorID).find("INPUT[name='domain']").val() || window.location.host;
    let csrf = $(selectorID).find("INPUT[name='csrf']").val() || "";
    let username = $(selectorID).find("INPUT[name='username']").val() || undefined;
    let password = $(selectorID).find("INPUT[name='password']").val() || undefined;
    let confirmPassword = $(selectorID).find("INPUT[name='confirm-password']").val() || "";
    let email = $(selectorID).find("INPUT[name='email']").val() || undefined;
    let tel = $(selectorID).find("INPUT[name='tel']").val() || undefined;

    hcore.security.initInterface(selectorID, redirect || "/user");


    if(confirmPassword !== password) {
        hcore.security.unblockAction();
        hcore.security.throwWarning('I campi "password" e "conferma password" non coincidono');
        return false;
    }

    $.ajax({
        url: (url || window.location.pathname),
        headers: {
            "domain": domain,
            "csrf": csrf
        },
        method: "POST",
        dataType: "json",
        data: {
            "username": username,
            "password": password,
            "email": email,
            "tel": tel,
        }
    })
        .done(function (response) {
            if (response.status === 0) {
                if (response.data.welcome) {
                    hcore.inject(response.data.welcome, selectorID);
                } else if (response.data.confirm) {
                    hcore.inject(response.data.confirm, selectorID);
                    hcore.security.throwSuccess('Check your ' + response.data.activation.sender);
                    hcore.security.setBearer(response.data.activation.token);
                }
            } else {
                hcore.security.unblockAction();
                hcore.security.throwWarning(response.error);
            }
        })
        .fail(hcore.security.responseFail);

    return false;
};