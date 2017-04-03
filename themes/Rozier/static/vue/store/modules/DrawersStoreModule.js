import _ from 'lodash'
import {
    DRAWERS_ADD_INSTANCE,
    DRAWERS_REMOVE_INSTANCE,
    DRAWERS_ADD_ITEM,
    DRAWERS_REMOVE_ITEM,
    DRAWERS_EDIT_INSTANCE,
    DRAWERS_UPDATE_LIST,
    DRAWERS_ENABLE_DROPZONE,
    DRAWERS_DISABLE_DROPZONE,
    DRAWERS_INIT_DATA_REQUEST,
    DRAWERS_INIT_DATA_REQUEST_SUCCESS,
    DRAWERS_INIT_DATA_REQUEST_FAILED,
    DRAWERS_INIT_DATA_REQUEST_EMPTY,

    KEYBOARD_EVENT_ESCAPE,

    EXPLORER_CLOSE
} from '../../types/mutationTypes'
import api from '../../api'

/**
 * State
 *
 * list: [{
 *    id: ...,
 *    isActive: false,
 *    items: []
 * }]
 */
const state = {
    list: [],
    trans: null,
    selectedDrawer: null
}

/**
 * Getters
 */
const getters = {
    drawersGetById: (state, getters) => (id) => {
        return state.list.find(drawer => drawer.id === id)
    }
}

/**
 * Actions
 */
const actions = {
    drawersAddInstance ({ commit }, drawer) {
        commit(DRAWERS_ADD_INSTANCE, { drawer })
    },
    drawersRemoveInstance ({ commit }, drawerToRemove) {
        commit(DRAWERS_REMOVE_INSTANCE, { drawerToRemove })
    },
    drawersInitData ({ commit }, { drawer, ids, entity }) {
        commit(DRAWERS_INIT_DATA_REQUEST, { drawer, entity })

        if (!ids || ids.length === 0 || !entity) {
            commit(DRAWERS_INIT_DATA_REQUEST_EMPTY, { drawer })
            return
        }

        api.getItemsByIds(entity, ids)
            .then((result) => {
                commit(DRAWERS_INIT_DATA_REQUEST_SUCCESS, { drawer, result })
            })
            .catch((error) => {
                commit(DRAWERS_INIT_DATA_REQUEST_FAILED, { drawer, error })
            })
    },
    drawersAddItem ({ commit, state }, { drawer, item, newIndex }) {
        let drawerToChange = state.selectedDrawer

        if (drawer) {
            drawerToChange = drawer
        }

        commit(DRAWERS_ADD_ITEM, { drawer: drawerToChange, item, newIndex })
    },
    drawersMoveItem ({ commit }, { drawer, item }) {

    },
    drawersRemoveItem ({ commit }, { drawer, item }) {
        commit(DRAWERS_REMOVE_ITEM, { drawer, item })
    },
    drawersExplorerButtonClick ({ commit, dispatch }, drawer) {
        commit(DRAWERS_EDIT_INSTANCE, { drawer })

        if (!state.selectedDrawer.isActive) {
            dispatch('explorerClose')
        } else {
            dispatch('explorerOpen', { entity: drawer.entity })
        }
    },
    drawersDropzoneButtonClick ({ state, dispatch }, drawer) {
        if (drawer.isDropzoneEnable) {
            dispatch('drawersDisableDropzone', { drawer })
        } else {
            dispatch('drawersEnableDropzone', { drawer })
        }
    },
    drawersEnableDropzone ({ commit }, { drawer }) {
        commit(DRAWERS_ENABLE_DROPZONE, { drawer })
    },
    drawersDisableDropzone ({ commit }, { drawer }) {
        commit(DRAWERS_DISABLE_DROPZONE, { drawer })
    }
}

/**
 * Mutations
 */
const mutations = {
    [DRAWERS_ADD_INSTANCE] (state, { drawer }) {
        state.list.push({
            id: drawer._uid,
            isActive: false,
            items: [],
            errorMessage: null,
            isLoading: false,
            isDropzoneEnable: false
        })
    },
    [DRAWERS_REMOVE_INSTANCE] (state, { drawerToRemove }) {
        state.list = _.remove(state.list, (drawer) => {
            return drawer._uid === drawerToRemove._uid
        })
    },
    [DRAWERS_EDIT_INSTANCE] (state, { drawer }) {
        // Disable other drawers
        state.list.forEach((item) => {
            if (item !== drawer) {
                item.isActive = false
            }
        })

        // Toggle current drawer
        drawer.isActive = !drawer.isActive

        // Define the drawer as current selected drawer
        state.selectedDrawer = drawer
    },
    [DRAWERS_ADD_ITEM] (state, { drawer, item, newIndex = 0 }) {
        drawer.items.push(item)
    },
    [DRAWERS_UPDATE_LIST] (state, { drawer, newList }) {
        drawer.items = newList
    },
    [DRAWERS_REMOVE_ITEM] (state, { drawer, item }) {
        let indexOf = drawer.items.indexOf(item)
        if (indexOf >= 0) {
            drawer.items.splice(indexOf, 1)
        }
    },
    [EXPLORER_CLOSE] (state) {
        state = disableActiveDrawer(state)
    },
    [DRAWERS_INIT_DATA_REQUEST_SUCCESS] (state, { drawer, result }) {
        drawer.isLoading = false
        drawer.items = result.items
        state.trans = result.trans
    },
    [DRAWERS_INIT_DATA_REQUEST] (state, { drawer, entity }) {
        drawer.isLoading = true
        drawer.entity = entity
    },
    [DRAWERS_INIT_DATA_REQUEST_FAILED] (state, { drawer, error }) {
        drawer.isLoading = false
        drawer.errorMessage = error.message
    },
    [DRAWERS_INIT_DATA_REQUEST_EMPTY] (state, { drawer }) {
        drawer.isLoading = false
    },
    [KEYBOARD_EVENT_ESCAPE] (state) {
        state = disableActiveDrawer(state)
    },
    [DRAWERS_ENABLE_DROPZONE] (state, { drawer }) {
        // Disable other dropzone
        state.list.forEach((drawer) => {
            drawer.isDropzoneEnable = false
        })

        drawer.isDropzoneEnable = true
    },
    [DRAWERS_DISABLE_DROPZONE] (state, { drawer }) {
        drawer.isDropzoneEnable = false
    },
}

function disableActiveDrawer (state) {
    state.list.forEach((drawer) => {
        drawer.isActive = false
        drawer.isDropzoneEnable = false
    })

    state.selectedDrawer = null

    return state
}

export default {
    state,
    getters,
    actions,
    mutations
}
