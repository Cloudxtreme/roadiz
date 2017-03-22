import {
    SET_NODE_TYPE
} from '../actions/nodeTypeFieldsActions'

const initialState = {
    type_value: 0
}

export default function (state = initialState, action) {
    switch (action.type) {
        case SET_NODE_TYPE:
            return setNodeType(state, action.value)
        default:
            return state
    }
}

function setNodeType(state, value) {
    return {
        ...state,
        type_value: value
    }
}
