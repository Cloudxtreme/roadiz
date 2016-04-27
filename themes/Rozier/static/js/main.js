/*
 * ============================================================================
 * Rozier entry point
 * ============================================================================
 */

var Rozier = {};

Rozier.$window = null;
Rozier.$body = null;

Rozier.windowWidth = null;
Rozier.windowHeight = null;
Rozier.resizeFirst = true;
Rozier.gMapLoading = false;
Rozier.gMapLoaded = false;

Rozier.searchNodesSourcesDelay = null;
Rozier.nodeTrees = [];
Rozier.treeTrees = [];

Rozier.$userPanelContainer = null;
Rozier.$minifyTreePanelButton = null;
Rozier.$mainTrees = null;
Rozier.$mainTreesContainer = null;
Rozier.$mainTreeElementName = null;
Rozier.$treeContextualButton = null;
Rozier.$nodesSourcesSearch = null;
Rozier.nodesSourcesSearchHeight = null;
Rozier.$nodeTreeHead = null;
Rozier.nodeTreeHeadHeight = null;
Rozier.$treeScrollCont = null;
Rozier.$treeScroll = null;
Rozier.treeScrollHeight = null;

Rozier.$mainContentScrollable = null;
Rozier.mainContentScrollableWidth = null;
Rozier.mainContentScrollableOffsetLeft = null;
Rozier.$backTopBtn = null;

Rozier.entriesPanel = null;


Rozier.onDocumentReady = function(event) {

    /*
     * Store Rozier configuration
     */
    for( var index in temp ){
        Rozier[index] = temp[index];
    }

    Rozier.lazyload = new Lazyload();
    Rozier.entriesPanel = new EntriesPanel();

    Rozier.$window = $(window);
    Rozier.$body = $('body');

    if(isMobile.any() === null) Rozier.centerVerticalObjects(); // this must be done before generalBind!


    // --- Selectors --- //
    Rozier.$userPanelContainer = $('#user-panel-container');
    Rozier.$minifyTreePanelButton = $('#minify-tree-panel-button');
    Rozier.$mainTrees = $('#main-trees');
    Rozier.$mainTreesContainer = $('#main-trees-container');
    Rozier.$nodesSourcesSearch = $('#nodes-sources-search');

    Rozier.$mainContentScrollable = $('#main-content-scrollable');
    Rozier.$backTopBtn = $('#back-top-button');

    // Pointer events polyfill
    if(!Modernizr.testProp('pointerEvents')){
        PointerEventsPolyfill.initialize({'selector':'#main-trees-overlay'});
    }


    // --- Events --- //

    // Search node
    $("#nodes-sources-search-input").on('focus', function(){
        $('#nodes-sources-search').addClass("focus-on");
        $('#nodes-sources-search-results').fadeIn();
        setTimeout(function(){ Rozier.resize(); }, 500);
    });
    $("#nodes-sources-search-input").on('focusout', function(){
        $('#nodes-sources-search-results').fadeOut();
        $('#nodes-sources-search').removeClass("focus-on");
        $(this).val("");
        setTimeout(function(){ Rozier.resize(); }, 500);
    });

    $("#nodes-sources-search-input").on('keyup', Rozier.onSearchNodesSources);
    $("#nodes-sources-search-form").on('submit', Rozier.onSubmitSearchNodesSources);

    // Minify trees panel toggle button
    Rozier.$minifyTreePanelButton.on('click', Rozier.toggleTreesPanel);

    // Back top btn
    Rozier.$backTopBtn.on('click', $.proxy(Rozier.backTopBtnClick, Rozier));

    Rozier.$window.on('resize', $.proxy(Rozier.resize, Rozier));
    Rozier.$window.trigger('resize');


    Rozier.lazyload.generalBind();
    Rozier.bindMainNodeTreeLangs();
};


/**
 * init nestable for ajax
 * @return {[type]} [description]
 */
Rozier.initNestables = function  () {
    var _this = this;

    $('.uk-nestable').each(function (index, element) {
        UIkit.nestable(element);
    });
};


/**
 * Bind main trees
 * @return {[type]} [description]
 */
Rozier.bindMainTrees = function () {
    var _this = this;

    // TREES
    $('.nodetree-widget .root-tree').off('change.uk.nestable');
    $('.nodetree-widget .root-tree').on('change.uk.nestable', Rozier.onNestableNodeTreeChange );

    $('.tagtree-widget .root-tree').off('change.uk.nestable');
    $('.tagtree-widget .root-tree').on('change.uk.nestable', Rozier.onNestableTagTreeChange );

    $('.foldertree-widget .root-tree').off('change.uk.nestable');
    $('.foldertree-widget .root-tree').on('change.uk.nestable', Rozier.onNestableFolderTreeChange );

    // Tree element name
    _this.$mainTreeElementName = _this.$mainTrees.find('.tree-element-name');
    _this.$mainTreeElementName.off('contextmenu', $.proxy(_this.maintreeElementNameRightClick, _this));
    _this.$mainTreeElementName.on('contextmenu', $.proxy(_this.maintreeElementNameRightClick, _this));
};


/**
 * Main tree element name right click
 * @return {[type]} [description]
 */
Rozier.maintreeElementNameRightClick = function(e){
    var _this = this;

    var $contextualMenu = $(e.currentTarget).parent().find('.tree-contextualmenu');
    if ($contextualMenu.length) {
        if($contextualMenu[0].className.indexOf('uk-open') == -1) {
            addClass($contextualMenu[0], 'uk-open');
        }
        else removeClass($contextualMenu[0], 'uk-open');
    }

    return false;

};


/**
 * Bind main node tree langs
 * @return {[type]} [description]
 */
Rozier.bindMainNodeTreeLangs = function () {
    var _this = this;

    $('body').on('click', '#tree-container .nodetree-langs a', function (event) {

        Rozier.lazyload.canvasLoader.show();
        var $link = $(event.currentTarget);
        var translationId = parseInt($link.attr('data-translation-id'));

        Rozier.refreshMainNodeTree(translationId);
        return false;
    });
};


/**
 * Get messages
 * @return {[type]} [description]
 */
Rozier.getMessages = function () {
    var _this = this;

    $.ajax({
        url: Rozier.routes.ajaxSessionMessages,
        type: 'GET',
        dataType: 'json',
        cache: false,
        data: {
            "_action": 'messages',
            "_token": Rozier.ajaxToken
        },
    })
    .done(function(data) {
        if (typeof data.messages !== "undefined") {

            if (typeof data.messages.confirm !== "undefined" &&
                        data.messages.confirm.length > 0) {

                for (var i = data.messages.confirm.length - 1; i >= 0; i--) {

                    UIkit.notify({
                        message : data.messages.confirm[i],
                        status  : 'success',
                        timeout : 2000,
                        pos     : 'top-center'
                    });
                }
            }

            if (typeof data.messages.error !== "undefined" &&
                        data.messages.error.length > 0) {

                for (var j = data.messages.error.length - 1; j >= 0; j--) {

                    UIkit.notify({
                        message : data.messages.error[j],
                        status  : 'error',
                        timeout : 2000,
                        pos     : 'top-center'
                    });
                }
            }
        }
    })
    .fail(function() {
        console.log("[Rozier.getMessages] error");
    });
};

Rozier.refreshAllNodeTrees = function (translationId) {
    var _this = this;

    _this.refreshMainNodeTree(translationId);

    /*
     * Stack trees
     */
    if(_this.lazyload.stackNodeTrees.treeAvailable()){
        _this.lazyload.stackNodeTrees.refreshNodeTree();
    }

    /*
     * Children node fields widgets;
     */
    if(_this.lazyload.childrenNodesFields.treeAvailable()) {
        for (var i = _this.lazyload.childrenNodesFields.$nodeTrees.length - 1; i >= 0; i--) {
            var $nodeTree = _this.lazyload.childrenNodesFields.$nodeTrees.eq(i);
            _this.lazyload.childrenNodesFields.refreshNodeTree($nodeTree);
        }
    }
};

/**
 * Refresh only main nodeTree.
 *
 */
Rozier.refreshMainNodeTree = function (translationId) {
    var _this = this;

    var $currentNodeTree = $('#tree-container').find('.nodetree-widget');
    var $currentRootTree = $($currentNodeTree.find('.root-tree')[0]);

    if($currentNodeTree.length){

        var postData = {
            "_token": Rozier.ajaxToken,
            "_action":'requestMainNodeTree'
        };

        if ($currentRootTree.length && !isset(translationId)) {
            translationId = parseInt($currentRootTree.attr('data-translation-id'));
        }

        var url = Rozier.routes.nodesTreeAjax;
        if(isset(translationId) && translationId > 0){
            url += '/'+translationId;
        }

        $.ajax({
            url: url,
            type: 'get',
            cache: false,
            dataType: 'json',
            data: postData,
        })
        .done(function(data) {
            if($currentNodeTree.length &&
                typeof data.nodeTree != "undefined"){

                $currentNodeTree.fadeOut('slow', function() {
                    $currentNodeTree.replaceWith(data.nodeTree);
                    $currentNodeTree = $('#tree-container').find('.nodetree-widget');
                    $currentNodeTree.fadeIn();
                    Rozier.initNestables();
                    Rozier.bindMainTrees();
                    Rozier.resize();
                    Rozier.lazyload.bindAjaxLink();
                    _this.lazyload.nodeTreeContextActions = new NodeTreeContextActions();
                });
            }
        })
        .fail(function(data) {
            console.log(data.responseJSON);
        })
        .always(function(){
            Rozier.lazyload.canvasLoader.hide();
        });
    } else {
        console.error("No main node-tree available.");
    }
};


/*
 * Center vetically every DOM objects that have
 * the data-vertical-center attribute
 */
Rozier.centerVerticalObjects = function(context) {
    var _this = this;

    // console.log('center vertical objects');
    // console.log(context);
    var $objects = $(".rz-vertical-align");

    for(var i = 0; i < $objects.length; i++) {
        $objects[i].style.top = '50%';
        $objects[i].style.marginTop = $($objects[i]).actual('outerHeight')/-2 +'px';
        if($objects[i].className.indexOf('actions-menu') >= 0 && context == 'ajax'){
            $objects[i].style.right = - $($objects[i]).actual('outerWidth')+'px';
        }
    }
};


/**
 * Toggle trees panel
 * @param  {[type]} event [description]
 * @return {[type]}       [description]
 */
Rozier.toggleTreesPanel = function (event) {
    var _this = this;

    $('#main-trees').toggleClass('minified');
    $('#minify-tree-panel-button i').toggleClass('uk-icon-rz-panel-tree-open');
    $('#minify-tree-panel-area').toggleClass('tree-panel-hidden');

    return false;
};


/**
 * Toggle user panel
 * @param  {[type]} event [description]
 * @return {[type]}       [description]
 */
Rozier.toggleUserPanel = function (event) {
    var _this = this;

    $('#user-panel').toggleClass('minified');

    return false;
};


/**
 * Handle ajax search node source.
 *
 * @param event
 */
Rozier.onSearchNodesSources = function (event) {
    var _this = this;

    var $input = $(event.currentTarget);

    if ($input.val().length > 2) {
        clearTimeout(Rozier.searchNodesSourcesDelay);
        Rozier.searchNodesSourcesDelay = setTimeout(function () {
            var postData = {
                _token: Rozier.ajaxToken,
                _action:'searchNodesSources',
                searchTerms: $input.val()
            };
            console.log(postData);
            $.ajax({
                url: Rozier.routes.searchNodesSourcesAjax,
                type: 'POST',
                dataType: 'json',
                data: postData
            })
            .done(function( data ) {
                console.log(data);

                if (typeof data.data != "undefined" &&
                    data.data.length > 0) {

                    $results = $('#nodes-sources-search-results');
                    $results.empty();

                    for(var i in data.data) {
                        $results.append('<li><a href="'+data.data[i].url+
                                '" style="border-left-color:'+data.data[i].typeColor+'"><span class="title">'+data.data[i].title+
                                '</span> <span class="type">'+data.data[i].typeName+
                                '</span></a></li>');
                    }
                    $results.append('<a id="see-all" href="#">'+Rozier.messages.see_all+'</a>'); //Trans message (base.html.twig)
                }
            })
            .fail(function( data ) {
                console.log(data);
            });
        }, 300);
    }
};


/**
 * On submit search nodes sources
 * @return {[type]} [description]
 */
Rozier.onSubmitSearchNodesSources = function(e){
    var _this = this;

    return false;
};


/**
 *
 * @param  Event event
 * @param  jQueryNode element
 * @param  string status  added, moved or removed
 * @return boolean
 */
Rozier.onNestableNodeTreeChange = function (event, element, status) {
    var _this = this;

    /*
     * If node removed, do not do anything, the othechange.uk.nestabler nodeTree will be triggered
     */
    if (status == 'removed') {
        return false;
    }

    var node_id = parseInt(element.attr('data-node-id'));
    var parent_node_id = null;
    if (element.parents('.nodetree-element').length) {
        parent_node_id = parseInt(element.parents('.nodetree-element').eq(0).attr('data-node-id'));
    } else if (element.parents('.stack-tree-widget').length) {
        parent_node_id = parseInt(element.parents('.stack-tree-widget').eq(0).attr('data-parent-node-id'));
    } else if (element.parents('.children-node-widget').length) {
        parent_node_id = parseInt(element.parents('.children-node-widget').eq(0).attr('data-parent-node-id'));
    }

    /*
     * When dropping to route
     * set parentNodeId to NULL
     */
    if(isNaN(parent_node_id)){
        parent_node_id = null;
    }

    /*
     * User dragged node inside itself
     * It will destroy the Internet !
     */
    if (node_id === parent_node_id) {
        console.log("You cannot move a node inside itself!");
        alert("You cannot move a node inside itself!");
        window.location.reload();
        return false;
    }

    var postData = {
        _token: Rozier.ajaxToken,
        _action: 'updatePosition',
        nodeId: node_id,
        newParent: parent_node_id,
    };

    /*
     * Get node siblings id to compute new position
     */
    if (element.next().length && typeof element.next().attr('data-node-id') !== "undefined") {
        postData.nextNodeId = parseInt(element.next().attr('data-node-id'));
    }
    else if(element.prev().length && typeof element.prev().attr('data-node-id') !== "undefined") {
        postData.prevNodeId = parseInt(element.prev().attr('data-node-id'));
    }

    console.log(postData);

    $.ajax({
        url: Rozier.routes.nodeAjaxEdit.replace("%nodeId%", node_id),
        type: 'POST',
        dataType: 'json',
        data: postData
    })
    .done(function( data ) {
        UIkit.notify({
            message : data.responseText,
            status  : data.status,
            timeout : 3000,
            pos     : 'top-center'
        });
    })
    .fail(function(data) {
        console.err(data);
    });
};


/**
 *
 * @param  Event event
 * @param  jQueryTag element
 * @param  string status  added, moved or removed
 * @return boolean
 */
Rozier.onNestableTagTreeChange = function (event, element, status) {
    var _this = this;

    /*
     * If tag removed, do not do anything, the other tagTree will be triggered
     */
    if (status == 'removed') {
        return false;
    }

    var tag_id = parseInt(element.attr('data-tag-id'));
    var parent_tag_id = null;
    if (element.parents('.tagtree-element').length) {
        parent_tag_id = parseInt(element.parents('.tagtree-element').eq(0).attr('data-tag-id'));
    }
    /*
     * When dropping to route
     * set parentTagId to NULL
     */
    if(isNaN(parent_tag_id)){
        parent_tag_id = null;
    }

    /*
     * User dragged tag inside itself
     * It will destroy the Internet !
     */
    if (tag_id === parent_tag_id) {
        console.log("You cannot move a tag inside itself!");
        alert("You cannot move a tag inside itself!");
        window.location.reload();
        return false;
    }

    var postData = {
        _token: Rozier.ajaxToken,
        _action: 'updatePosition',
        tagId: tag_id,
        newParent: parent_tag_id,
    };

    /*
     * Get tag siblings id to compute new position
     */
    if (element.next().length && typeof element.next().attr('data-tag-id') !== "undefined") {
        postData.nextTagId = parseInt(element.next().attr('data-tag-id'));
    }
    else if (element.prev().length && typeof element.prev().attr('data-tag-id') !== "undefined") {
        postData.prevTagId = parseInt(element.prev().attr('data-tag-id'));
    }

    $.ajax({
        url: Rozier.routes.tagAjaxEdit.replace("%tagId%", tag_id),
        type: 'POST',
        dataType: 'json',
        data: postData
    })
    .done(function(data) {
        UIkit.notify({
            message : data.responseText,
            status  : data.status,
            timeout : 3000,
            pos     : 'top-center'
        });

    })
    .fail(function(data) {
        console.err(data);
    });
};

/**
 *
 * @param  Event event
 * @param  jQueryFolder element
 * @param  string status  added, moved or removed
 * @return boolean
 */
Rozier.onNestableFolderTreeChange = function (event, element, status) {
    var _this = this;

    /*
     * If folder removed, do not do anything, the other folderTree will be triggered
     */
    if (status == 'removed') {
        return false;
    }

    var folder_id = parseInt(element.attr('data-folder-id'));
    var parent_folder_id = null;
    if (element.parents('.foldertree-element').length) {
        parent_folder_id = parseInt(element.parents('.foldertree-element').eq(0).attr('data-folder-id'));
    }
    /*
     * When dropping to route
     * set parentFolderId to NULL
     */
    if(isNaN(parent_folder_id)){
        parent_folder_id = null;
    }

    /*
     * User dragged folder inside itself
     * It will destroy the Internet !
     */
    if (folder_id === parent_folder_id) {
        console.log("You cannot move a folder inside itself!");
        alert("You cannot move a folder inside itself!");
        window.location.reload();
        return false;
    }

    var postData = {
        _token: Rozier.ajaxToken,
        _action: 'updatePosition',
        folderId: folder_id,
        newParent: parent_folder_id,
    };

    /*
     * Get folder siblings id to compute new position
     */
    if (element.next().length && typeof element.next().attr('data-folder-id') !== "undefined") {
        postData.nextFolderId = parseInt(element.next().attr('data-folder-id'));
    }
    else if(element.prev().length && typeof element.prev().attr('data-folder-id') !== "undefined") {
        postData.prevFolderId = parseInt(element.prev().attr('data-folder-id'));
    }

    $.ajax({
        url: Rozier.routes.folderAjaxEdit.replace("%folderId%", folder_id),
        type: 'POST',
        dataType: 'json',
        data: postData
    })
    .done(function(data) {
        UIkit.notify({
            message : data.responseText,
            status  : data.status,
            timeout : 3000,
            pos     : 'top-center'
        });

    })
    .fail(function(data) {
        console.err(data);
    });
};


/**
 * Back top click
 * @return {[type]} [description]
 */
Rozier.backTopBtnClick = function(e){
    var _this = this;

    TweenLite.to(_this.$mainContentScrollable, 0.6, {scrollTo:{y:0}, ease:Expo.easeOut});

    return false;
};


/**
 * Resize
 * @return {[type]} [description]
 */
Rozier.resize = function(){
    var _this = this;

    _this.windowWidth = _this.$window.width();
    _this.windowHeight = _this.$window.height();

    // Close tree panel if small screen & first resize
    if(_this.windowWidth > 768 &&
        _this.windowWidth <= 1200 &&
        _this.resizeFirst) {
        _this.$mainTrees[0].style.display = 'none';
        _this.$minifyTreePanelButton.trigger('click');
        setTimeout(function(){
            _this.$mainTrees[0].style.display = 'table-cell';
        }, 1000);
    }

    // Check if mobile
    if(_this.windowWidth <= 768 && _this.resizeFirst) _this.mobile = new RozierMobile(); // && isMobile.any() !== null


    // Set height to panels (fix for IE9,10)
    if(isMobile.any() === null){
        _this.$userPanelContainer.height(_this.windowHeight);
        _this.$mainTreesContainer.height(_this.windowHeight);
    }
    _this.$mainContentScrollable.height(_this.windowHeight);

    // Tree scroll height
    _this.$nodeTreeHead = _this.$mainTrees.find('.nodetree-head');
    _this.$treeScrollCont = _this.$mainTrees.find('.tree-scroll-cont');
    _this.$treeScroll = _this.$mainTrees.find('.tree-scroll');

    /*
     * need actual to get tree height even when they are hidden.
     */
    _this.nodesSourcesSearchHeight = _this.$nodesSourcesSearch.actual('outerHeight');
    _this.nodeTreeHeadHeight = _this.$nodeTreeHead.actual('outerHeight');
    _this.treeScrollHeight = _this.windowHeight - (_this.nodesSourcesSearchHeight + _this.nodeTreeHeadHeight);

    if(isMobile.any() !== null) _this.treeScrollHeight = _this.windowHeight - (50 + 50 + _this.nodeTreeHeadHeight); // Menu + tree menu + tree head

    for(var i = 0; i < _this.$treeScrollCont.length; i++) {
        _this.$treeScrollCont[i].style.height = _this.treeScrollHeight + 'px';
    }

    // Main content
    _this.mainContentScrollableWidth = _this.$mainContentScrollable.width();
    _this.mainContentScrollableOffsetLeft = _this.windowWidth - _this.mainContentScrollableWidth;

    _this.lazyload.resize();
    _this.entriesPanel.replaceSubNavs();

    // Documents list
    if(_this.lazyload !== null && !_this.resizeFirst) _this.lazyload.documentsList.resize();

    // Set resize first to false
    if(_this.resizeFirst) _this.resizeFirst = false;

};


/*
 * ============================================================================
 * Plug into jQuery standard events
 * ============================================================================
 */
$(document).ready(Rozier.onDocumentReady);
