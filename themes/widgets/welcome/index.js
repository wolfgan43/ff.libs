setTimeout(function() {
    if(window.location.pathname === "/login") {
        window.location.href = "/";
    } else {
        window.location.reload();
    }
}, 1000);