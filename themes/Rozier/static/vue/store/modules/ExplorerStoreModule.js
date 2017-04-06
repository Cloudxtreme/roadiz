import Vue from 'vue'
import api from '../../api'
import {
    EXPLORER_REQUEST,
    EXPLORER_SUCCESS,
    EXPLORER_RESET,
    EXPLORER_FAILED,
    EXPLORER_OPEN,
    EXPLORER_CLOSE,
    EXPLORER_LOAD_MORE,
    EXPLORER_LOAD_MORE_SUCCESS,
    EXPLORER_IS_LOADED,
    EXPLORER_UPDATE_FILTERS,
    EXPLORER_UPDATE_SEARCH_TERMS,

    FILTER_EXPLORER_UPDATE,

    KEYBOARD_EVENT_ESCAPE
} from '../../types/mutationTypes'

import {
    DOCUMENT_ENTITY,
    NODE_ENTITY,
    JOIN_ENTITY
} from '../../types/entityTypes'

import DocumentPreviewListItem from '../../components/DocumentPreviewListItem.vue'
import NodePreviewItem from '../../components/NodePreviewItem.vue'
import JoinPreviewItem from '../../components/JoinPreviewItem.vue'

/**
 * Module state
 */
const initialState = {
    searchTerms: '',
    isOpen: false,
    isLoading: false,
    isLoadingMore: false,
    items: [],
    trans: {
        moreItems: ''
    },
    filters: {},
    entity: null,
    error: '',
    currentListingView: null,
    isFilterEnable: null,
    filterExplorerIcon: 'uk-icon-cog'
}

const state = { ...initialState }

/**
 * Getters
 */
const getters = {
    getExplorerEntity: state => state.entity,
}

/**
 * Actions
 */
const actions =  {
    async explorerOpen ({ commit, dispatch, state }, { entity }) {
        // Prevent if panel is already open
        if (state.isOpen) return

        // Reset explorer
        commit(EXPLORER_RESET)

        // Open panel explorer
        commit(EXPLORER_OPEN, { entity })

        // Make the search
        await dispatch('explorerMakeSearch')

        commit(EXPLORER_IS_LOADED)
    },
    explorerClose ({ commit, dispatch }) {
        dispatch('filterExplorerClose')
        commit(EXPLORER_RESET)
        commit(EXPLORER_CLOSE)
    },
    explorerToggle ({ dispatch, state }) {
        if (state.isOpen) {
            dispatch('explorerClose')
        } else {
            dispatch('explorerOpen')
        }
    },
    explorerUpdateSearch ({ commit, dispatch }, { searchTerms }) {
        commit(EXPLORER_UPDATE_SEARCH_TERMS, { searchTerms })
        dispatch('explorerMakeSearch')
    },
    explorerMakeSearch ({ commit, state, getters }) {
        const entity = state.entity
        const searchTerms = state.searchTerms
        const preFilters = getters.getDrawerFilters
        const filters = state.filters
        const filterExplorerSelection = getters.getFilterExplorerSelectedItem
        const moreData = state.isLoadingMore

        commit(EXPLORER_REQUEST)

        return api.getExplorerItems({
            entity,
            searchTerms,
            preFilters,
            filters,
            filterExplorerSelection,
            moreData
        })
            .then((result) => {
                commit(EXPLORER_SUCCESS, { result })
            })
            .catch((error) => {
                console.error(error)
                commit(EXPLORER_FAILED, { error })
            })
    },
    async explorerLoadMore ({ commit, dispatch }) {
        commit(EXPLORER_LOAD_MORE)

        await dispatch('explorerMakeSearch')

        commit(EXPLORER_LOAD_MORE_SUCCESS)
    }
}

/**
 * Mutations
 */
const mutations = {
    [EXPLORER_REQUEST] (state) {
        if (!state.isLoadingMore) {
            state.isLoading = true
        }
    },
    [EXPLORER_UPDATE_SEARCH_TERMS] (state, { searchTerms }) {
        state.searchTerms = searchTerms
    },
    [EXPLORER_SUCCESS] (state, { result }) {
        state.isLoading = false

        if (state.isLoadingMore) {
            state.items = [...state.items, ...result.items]
        } else {
            state.items = result.items
        }

        state.filters = result.filters
    },
    [EXPLORER_LOAD_MORE] (state) {
        state.isLoadingMore = true
    },
    [EXPLORER_LOAD_MORE_SUCCESS] (state) {
        state.isLoadingMore = false
    },
    [FILTER_EXPLORER_UPDATE] (state) {
        state.filters = {}
    },
    [EXPLORER_RESET] (state) {
        // Reset state
        for (let f in state) {
            if (state.hasOwnProperty(f)) {
                Vue.set(state, f, initialState[f]);
            }
        }
    },
    [EXPLORER_FAILED] (state) {
        state.isLoading = false
        state.isLoadingMore = false
        state.error = 'Request failed'
    },
    [EXPLORER_OPEN] (state, { entity }) {
        state.isOpen = true
        state.isLoading = true
        state.entity = entity
        state.trans.moreItems = ''

        // Define specific config for each entity type
        switch (entity) {
            case DOCUMENT_ENTITY:
                state.currentListingView = DocumentPreviewListItem
                state.filterExplorerIcon = 'uk-icon-rz-folder-tree-mini'
                state.trans.moreItems = 'moreDocuments'
                state.isFilterEnable = true
                break;
            case NODE_ENTITY:
                state.currentListingView = NodePreviewItem
                state.filterExplorerIcon = 'uk-icon-tags'
                state.trans.moreItems = 'moreNodes'
                state.isFilterEnable = true
                break;
            case JOIN_ENTITY:
                state.currentListingView = JoinPreviewItem
                state.trans.moreItems = 'moreEntities'
                state.isFilterEnable = false
                break;
        }
    },
    [EXPLORER_IS_LOADED] (state) {
        state.isLoading = false
    },
    [EXPLORER_CLOSE] (state) {
        state.isOpen = false
        state.isLoading = false
    },
    [KEYBOARD_EVENT_ESCAPE] () {
        state.isOpen = false
    }
}

export default {
    state,
    getters,
    actions,
    mutations
}
