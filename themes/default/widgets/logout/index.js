hcore.security.logout = function (url, redirect, selector) {
    var selectorID = (selector
            ? "#" + selector
            : "#logout-box"
    );

    hcore.security.initInterface(selectorID, redirect);

    $.ajax({
        url: (url || window.location.pathname),
        headers: {
        },
        method: 'POST',
        dataType: 'json',
        data: {}
    })
    .done(function (response) {
        if (response.status === 0) {
            hcore.security.redirect();
        } else {
            hcore.security.unblockAction();
            hcore.security.throwException(response.error);
        }
    })
    .fail(hcore.security.responseFail);

    return false;
};
