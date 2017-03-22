/*
 * action types
 */
export const BUTTON_INCREMENT = 'BUTTON_INCREMENT'

/*
 * action creators
 */
export function increment () {
    return (dispatch, getState) => {
        dispatch({
            type: BUTTON_INCREMENT,
        })
    }
}
