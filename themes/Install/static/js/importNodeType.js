var ImportNodeType = function ( routesArray ) {
    var _this = this;

    _this.routes = routesArray;

    _this.always(0);
};

ImportNodeType.prototype.routes = null;
ImportNodeType.prototype.score = 0;

ImportNodeType.prototype.always = function( index) {
    var _this = this;

    if(_this.routes.length > index) {
        if (typeof _this.routes[index].update != "undefined") {
            $.ajax({
                url:_this.routes[index].update,
                type: 'GET',
                dataType: 'json',
                complete: function() {
                    console.log("updateSchema");
                    _this.callSingleImport(index);
                }
            });
        } else {
            _this.callSingleImport(index);
        }
    } else {
        $('#next-step-button').removeClass('uk-button-disabled');
    }
};

ImportNodeType.prototype.callSingleImport = function( index ) {
    var _this = this;

    var $row = $("#"+_this.routes[index].id);
    var $icon = $row.find("i");
    $icon.removeClass('uk-icon-circle-o');
    $icon.addClass('uk-icon-spin');
    $icon.addClass('uk-icon-spinner');

    $.ajax({
        url: _this.routes[index].url,
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            console.log("success");
            console.log(data);

            $icon.removeClass('uk-icon-spinner');
            $icon.addClass('uk-icon-check');
            $row.addClass('uk-badge-success');
        },
        error: function(data) {
            $icon.removeClass('uk-icon-spinner');
            $icon.addClass('uk-icon-warning');
            $row.addClass('uk-badge-danger');

            if (typeof data.responseJSON != "undefined" && typeof data.responseJSON.error != "undefined") {
                $row.parent().parent().after("<tr><td class=\"uk-alert uk-alert-danger\" colspan=\"3\">"+data.responseJSON.error+"</td></tr>");
            }
        },
        complete: function(data) {
            console.log("complete");
            console.log(index);
            $icon.removeClass('uk-icon-spin');
            _this.always(index + 1);
        }
    });
    //}
};
