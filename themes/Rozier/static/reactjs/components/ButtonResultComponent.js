import React, { Component, PropTypes } from 'react'

export default class ButtonResultComponent extends Component {
    render () {
        const { total } = this.props

        return (
            <div>
                <strong>{total}</strong>
            </div>
        )
    }
}

ButtonResultComponent.propTypes = {
    total: PropTypes.number.isRequired
}
