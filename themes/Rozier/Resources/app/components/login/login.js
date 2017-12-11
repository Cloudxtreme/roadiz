import $ from 'jquery'

(function () {
    var onLoad = function (data) {
        const $splashContainer = $('#splash-container')
        $splashContainer.css({
            'background-image': 'url(' + data.url + ')'
        })
        $splashContainer.addClass('visible')
    }

    var requestImage = function () {
        $.ajax({
            url: window.RozierRoot.routes.splashRequest,
            type: 'GET',
            cache: false,
            dataType: 'json'
        })
        .done(function (data) {
            if (data === false) {
                requestImage()
            } else if (typeof data.url !== 'undefined') {
                let myImage = new Image(window.width, window.height)
                myImage.src = data.url
                myImage.onload = $.proxy(onLoad, this, data)
            }
        })
    }

    if (typeof window.RozierRoot.routes.splashRequest !== 'undefined') {
        requestImage()
    }
})()
