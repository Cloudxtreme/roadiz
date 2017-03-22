import React from 'react';
import { createStore, compose, applyMiddleware } from 'redux';
import thunkMiddleware from 'redux-thunk';
import rootReducer from '../reducers';
import createLogger from 'redux-logger';

/**
 * Logger middleware
 */
const loggerMiddleware = createLogger()

/**
 * Create a new Redux Store with middlewares
 * @param initialState
 * @returns {*}
 */
export function configureStore(initialState) {
    return createStore(
        rootReducer,
        initialState,
        compose(
            applyMiddleware(
                thunkMiddleware,
                loggerMiddleware
            )
        )
    );
}
