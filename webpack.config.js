var config = require('madewithlove-webpack-config').default({
    react: false,
    angular: true,
}).merge({
    module: {
        loaders: [
            {
                test: /\.md/,
                loader: 'html!markdown',
            }
        ]
    }
});

module.exports = config;
