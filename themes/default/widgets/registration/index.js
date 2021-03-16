hcore.security.registration = function (url, redirect, selector) {
    let selectorID = (selector
            ? "#" + selector
            : "#registration-box"
    );

    let token = $(selectorID).find("INPUT[name='csrf']").val() || "";

    let password = $(selectorID).find("INPUT[name='password']").val() || undefined;
    let confirmPassword = $(selectorID).find("INPUT[name='confirm-password']").val() || "";
    let email = $(selectorID).find("INPUT[name='email']").val() || undefined;
    let tel = $(selectorID).find("INPUT[name='tel']").val() || undefined;

    hcore.security.identifier = $(selectorID).find("INPUT[name='username']").val() || hcore.security.identifier || undefined;
    hcore.security.initInterface(selectorID, redirect || "/user");


    if(confirmPassword !== password) {
        hcore.security.unblockAction();
        hcore.security.throwWarning('I campi "password" e "conferma password" non coincidono');
        return false;
    }

    let headers = {
        "csrf": token
    };

    let data = {
        "identifier": hcore.security.identifier,
        "password": password,
        "email": email,
        "tel": tel
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
            } else if (response.data.confirm) {
                hcore.inject(response.data.confirm, selectorID);
                hcore.security.throwSuccess('Check your ' + response.data.sender);
                hcore.security.setBearer(response.data.token);
            }
        } else {
            hcore.security.unblockAction();
            hcore.security.throwWarning(response.error);
        }
    })
    .fail(hcore.security.responseFail);

    return false;
};