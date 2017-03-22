/*
 * action types
 */
export const SET_NODE_TYPE = 'SET_NODE_TYPE'

/*
 * action creators
 */
export function setNodeType (value) {
    return (dispatch, getState) => {
        dispatch({
            type: SET_NODE_TYPE,
            value
        })
    }
}
