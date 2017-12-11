import $ from 'jquery'
import {
    addClass
} from '../plugins'

/**
 * NODE TREE
 */
export default function NodeTree () {
    var _this = this

    // Selectors
    _this.$content = $('.content-node-tree')
    _this.$elements = null
    _this.$dropdown = null

    // Methods
    if (_this.$content.length) {
        _this.$dropdown = _this.$content.find('.uk-dropdown-small')
        _this.init()
    }
}

/**
 * Init
 * @return {[type]} [description]
 */
NodeTree.prototype.init = function () {
    var _this = this

    _this.contentHeight = _this.$content.actual('outerHeight')

    if (_this.contentHeight >= (window.Rozier.windowHeight - 400)) _this.dropdownFlip()
}

/**
 * Flip dropdown
 * @return {[type]}       [description]
 */
NodeTree.prototype.dropdownFlip = function () {
    var _this = this

    for (var i = _this.$dropdown.length - 1; i >= _this.$dropdown.length - 3; i--) {
        addClass(_this.$dropdown[i], 'uk-dropdown-up')
    }
}
