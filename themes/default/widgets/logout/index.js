hcore.Auth.logout = function (url, redirect, selector) {
    var selectorID = (selector
            ? "#" + selector
            : "#logout-box"
    );

    let csrf = jQuery(selectorID).find("INPUT[name='csrf']").val() || "";

    hcore.Auth.initInterface(selectorID, redirect);

    $.ajax({
        url: (url || window.location.pathname),
        headers: {
            "csrf": csrf
        },
        method: 'POST',
        dataType: 'medreq.json',
        data: {}
    })
    .done(function (response) {
        if (response.status === 0) {
            hcore.Auth.redirect();
        } else {
            hcore.Auth.unblockAction();
            hcore.Auth.throwException(response.error);
        }
    })
    .fail(hcore.Auth.responseFail);

    return false;
};
