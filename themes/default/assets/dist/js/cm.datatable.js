cm.dataTable = (function (id) {
    let form = document.getElementById(id);

    let self = {
        "page" : function(page) {
            form.querySelector("INPUT.dt-page").value = page;
            form.submit();
        },
        "order" : function(order) {
            form.querySelector("INPUT.dt-order").value = order;
            form.submit();
        },
        "search" : function(event) {
            if(event.keyCode === 13) {
                this.page(1);
            }
        },
        "length" : function() {
            this.page(1);
        }
    };

    return self;
});