module.exports = {
    entry: './reactjs/App.js',
    output: {
        filename: './dist/reactjs-bundle.js'
    },
    module: {
        rules: [
            {
                test: /\.js$/,
                exclude: /(node_modules|bower_components)/,
                loader: 'babel-loader',
                query: {
                    presets: ['env']
                }
            }
        ]
    },
    watch: true
}
