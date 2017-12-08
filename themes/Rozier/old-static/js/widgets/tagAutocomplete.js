/*
 *
 *
 */
var TagAutocomplete = function () {
    var _this = this;

    _this.$input = $(".rz-tag-autocomplete").eq(0);
    _this.initialUrl = _this.$input.attr('data-get-url');
    _this.placeholder = _this.$input.attr('placeholder');
    _this.initialTags = [];

    function split( val ) {
        return val.split( /,\s*/ );
    }
    function extractLast( term ) {
        return split( term ).pop();
    }

    function initAutocomplete() {
        _this.$input.tagEditor({
            autocomplete: {
                delay: 0.3, // show suggestions immediately
                position: { collision: 'flip' }, // automatic menu position up/down
                source: function( request, response ) {
                    $.getJSON( Rozier.routes.tagAjaxSearch, {
                        '_action': 'tagAutocomplete',
                        '_token': Rozier.ajaxToken,
                        'search': extractLast( request.term )
                    }, response);
                }
            },
            placeholder: _this.placeholder,
            initialTags: _this.initialTags,
            animateDelete: 0
        });
    }

    if (typeof _this.initialUrl !== "undefined" &&
        _this.initialUrl !== "") {
        $.getJSON(
            _this.initialUrl,
            {
                '_action': 'getNodeTags',
                '_token': Rozier.ajaxToken
            }, function (data) {
                _this.initialTags = data;
                initAutocomplete();
            }
        );
    } else {
        initAutocomplete();
    }
};
