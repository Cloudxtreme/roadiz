import React from 'react'
import { render, unmountComponentAtNode } from 'react-dom'
import { configureStore } from './store/configureStore'

// Containers
import ButtonContainer from './containers/ButtonContainer'
import RzSelectContainer from './containers/RzSelectContainer'

/**
 * Main ReactApp entry
 * Add dynamic features to Rozier Theme
 */
export default class ReactApp {
    constructor () {
        this.store = null
        this.init()
    }

    /**
     * ReactApp initialization
     * Init store, render displayed components and add eventlisteners
     */
    init () {
        this.initStore()

        // Render all components
        this.renderComponents()

        // Listen window event
        window.addEventListener('pageloaded', this.pageLoaded.bind(this))
        window.addEventListener('pagechange', this.pageChange.bind(this))
    }

    /**
     * Init common data store
     */
    initStore () {
        const state = window.__initialState__ || undefined
        this.store = configureStore(state)
    }

    /**
     * When a change page occurred
     * Unmount components displayed in ajax container (#main-content-scrollable)
     */
    pageChange () {
        // Looking for data-reactroot elements in page
        const $reactEls = $('#main-content-scrollable *[data-reactroot]')

        $reactEls.each((i, el) => {
            unmountComponentAtNode($(el).parent().get(0))
        })
    }

    /**
     * When the new page content is loaded
     * We mount and render new loaded components
     */
    pageLoaded () {
        this.renderComponents()
    }

    /**
     * Render new displayed components
     */
    renderComponents () {
        // Looking for react components
        const $reactComponents = $('*[data-react]')

        $reactComponents.each((i, el) => {
            this.renderComponent($(el))
        })
    }

    /**
     * Render a component
     * @param el The root DOM element to mount
     */
    renderComponent (el) {
        // Get the componnent name from the data-react attribute
        const componentName = el.data('react')

        // Return if no component name
        if (!componentName) return

        // Init component from his name
        switch (componentName) {
            case 'button-container':
                render(<ButtonContainer store={this.store} />, el.get(0))
                break
            case 'rz-select-container':
                render(<RzSelectContainer store={this.store} />, el.get(0))
                break
        }

        // Then remove data-react attribute
        el.removeAttr('data-react')
    }
}

// Create and launch new ReactApp
const reactApp = new ReactApp()
