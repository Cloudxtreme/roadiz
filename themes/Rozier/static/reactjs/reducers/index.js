import { combineReducers } from 'redux'

// Reducers
import button from './buttonReducer'
import nodeTypeField from './nodeTypeFieldReducer'

// Combine reducers
const rootReducer = combineReducers({
    button,
    nodeTypeField
})

export default rootReducer
