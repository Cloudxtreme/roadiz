import React, { Component, PropTypes } from 'react'
import { connect } from 'react-redux'
import { bindActionCreators } from 'redux'

import * as ButtonActions from '../actions/buttonActions'

// Components
import ButtonComponent from '../components/ButtonComponent'
import ButtonResultComponent from '../components/ButtonResultComponent'

class ButtonContainer extends Component {
    constructor(props) {
        super(props)
    }

    componentWillUnmount () {
        console.log('componentWillUnmount()')
    }

    render () {
        const { increment, total } = this.props

        return (
            <div>
                <ButtonResultComponent total={total} />
                <ButtonComponent onClick={increment} />
            </div>
        )
    }
}

ButtonContainer.propTypes = {
    total: PropTypes.number.isRequired,
    increment: PropTypes.func.isRequired
}

const mapStateToProps = (state) => {
    return {
        total: state.button.total
    }
}

const mapDispatchToProps = (dispatch) => {
    return {
        increment: bindActionCreators(ButtonActions.increment, dispatch)
    }
}

export default connect(mapStateToProps, mapDispatchToProps)(ButtonContainer)
