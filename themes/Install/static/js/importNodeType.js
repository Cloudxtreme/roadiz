var ImportNodeType = function ( routesArray ) {
    var _this = this;

    _this.routes = routesArray;

    _this.callSingleImport(0);
};

ImportNodeType.prototype.routes = null;
ImportNodeType.prototype.score = 0;

ImportNodeType.prototype.always = function( index, data ) {
    var _this = this;

    if (typeof data != "undefined" && typeof data.request != "undefined") {
        $.ajax({
            url:data.request,
            type: 'GET',
            dataType: 'json'
        })
        .always(function() {
            console.log("updateSchema");
            _this.callSingleImport(index + 1);
        });
    } else {
        _this.callSingleImport(index + 1);
    }
};

ImportNodeType.prototype.callSingleImport = function( index ) {
    var _this = this;

    if(_this.routes.length > index){

        var $row = $("#"+_this.routes[index].id);
        var $icon = $row.find("i");
        $icon.removeClass('uk-icon-circle-o');
        $icon.addClass('uk-icon-spin');
        $icon.addClass('uk-icon-spinner');

        $.ajax({
            url: _this.routes[index].url,
            type: 'GET',
            dataType: 'json'
        })
        .done(function(data) {
            console.log("success");
            console.log(data);

            $icon.removeClass('uk-icon-spinner');
            $icon.addClass('uk-icon-check');
            $row.addClass('uk-badge-success');
            _this.always(index, data);
        })
        .fail(function(data) {

            $icon.removeClass('uk-icon-spinner');
            $icon.addClass('uk-icon-warning');
            $row.addClass('uk-badge-danger');

            if (typeof data.responseJSON != "undefined" && typeof data.responseJSON.error != "undefined") {
                $row.parent().parent().after("<tr><td class=\"uk-alert uk-alert-danger\" colspan=\"3\">"+data.responseJSON.error+"</td></tr>");
            }
            _this.always(index, data.responseJSON);
        })
        .always(function(data) {
            console.log("complete");
            console.log(data);
            $icon.removeClass('uk-icon-spin');

        });
    } else {
        $('#next-step-button').removeClass('uk-button-disabled');
    }
};