import {
    BUTTON_INCREMENT
} from '../actions/buttonActions'

const initialState = {
    total: 0
}

export default function (state = initialState, action) {
    switch (action.type) {
        case BUTTON_INCREMENT:
            return setIncrement(state)
        default:
            return state
    }
}

function setIncrement(state) {
    return {
        ...state,
        total: state.total + 10
    }
}
