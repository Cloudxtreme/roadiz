import React, { Component, PropTypes } from 'react'
import { connect } from 'react-redux'
import { bindActionCreators } from 'redux'

import * as NodeTypeFieldsActions from '../actions/nodeTypeFieldsActions'

// Components
import OptionComponent from '../components/OptionComponent'

class RzSelectContainer extends Component {
    constructor (props) {
        super(props)

        this.options = [{
            value: 1,
            content: '1'
        }, {
            value: 2,
            content: '2'
        }]

        this.handleChange = this.handleChange.bind(this);
    }

    handleChange (event) {
        this.props.setNodeType(event.target.value)
    }

    createMarkup () {
        return { __html: this.props.html };
    }

    render () {
        const { type_value } = this.props

        return (
            <select value={type_value} onChange={this.handleChange}>
                {this.renderOptions()}
            </select>
        )
    }

    renderOptions () {
        return this.options.map((option, index) => {
            return (<OptionComponent value={option.value} content={option.content} key={index} />)
        })
    }
}

RzSelectContainer.propTypes = {
    setNodeType: PropTypes.func.isRequired
}

const mapStateToProps = (state) => {
    return {
        type_value: state.nodeTypeField.type_value
    }
}

const mapDispatchToProps = (dispatch) => {
    return {
        setNodeType: bindActionCreators(NodeTypeFieldsActions.setNodeType, dispatch)
    }
}

export default connect(mapStateToProps, mapDispatchToProps)(RzSelectContainer);
