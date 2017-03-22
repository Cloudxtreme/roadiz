import React, { Component, PropTypes } from 'react'

export default class ButtonComponent extends Component {
    componentWillUnmount () {
        console.log('componentWillUnmount()')
    }

    render () {
        const { onClick } = this.props

        return (
            <div>
                <button onClick={(e) => onClick()}>+10</button>
            </div>
        )
    }
}

ButtonComponent.propTypes = {
    onClick: PropTypes.func.isRequired
}
