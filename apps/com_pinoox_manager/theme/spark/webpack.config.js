const path = require('path');
const glob_all = require('glob-all');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const WebpackCleanPlugin = require('webpack-clean-plugin');
const Notification = require('./setup/plugins/notification');
const Manifest = require('./setup/plugins/manifest');
const TerserJSPlugin = require('terser-webpack-plugin');
const OptimizeCSSAssetsPlugin = require('optimize-css-assets-webpack-plugin');
const PurifyCSSPlugin = require('purgecss-webpack-plugin');
const CopyPlugin = require('copy-webpack-plugin');
const VueLoaderPlugin = require('vue-loader/lib/plugin');
const isRelease = (process.env.NODE_ENV === 'release');
const webpack = require('webpack');

module.exports = {
    entry: {
        main: [
            "./assets/js/main.js",
            "./assets/less/main.less",
        ],
    },
    output: {
        filename: `[name].js${isRelease ? '?[chunkhash]' : ''}`,
        path: path.resolve(__dirname, 'dist'),
    },
    module: {
        rules: [
            {
                test: /\.css$/,
                use: [MiniCssExtractPlugin.loader, 'css-loader'],
            },
            {
                test: /\.less$/,
                use: [MiniCssExtractPlugin.loader, 'css-loader', 'less-loader'],


            },
            {
                test: /\.vue$/,
                loader: 'vue-loader'
            },
            {
                test: /\.js$/,
                exclude: /node_modules/,
                use: {
                    loader: "babel-loader",
                    options: {
                        plugins: ['syntax-dynamic-import'],
                        presets: [
                            [
                                '@babel/preset-env',
                                {
                                    modules: false
                                }
                            ]
                        ]
                    }
                }
            },
            {
                test: /\.(png|jpe?g|gif|svg)$/,
                exclude: /fonts/,
                use: [
                    {
                        loader: 'file-loader',
                        options: {
                            name: 'images/[name].[hash].[ext]',
                        }
                    },
                    {
                        loader: 'image-webpack-loader',
                        options: {
                            webp: {
                                quality: 75,
                            }
                        }
                    }
                ]
            },

            {
                test: /\.(eot|ttf|woff|woff2|svg)$/,
                exclude: /images/,
                loader: 'file-loader',
                options: {
                    name: 'fonts/[name].[ext]',
                },
            },
        ]
    },
    mode: !isRelease ? 'development' : 'production',
    plugins: [
        new webpack.ProgressPlugin(),
        new CopyPlugin([
            {
                from: './assets/images/backgrounds/*.jpg',
                to: 'images/backgrounds',
                flatten: true,
            },
            {
                from: './assets/js/pinoox.js',
                flatten: true,
            },
        ]),
        new MiniCssExtractPlugin({
            filename: `[name].css${isRelease ? '?[chunkhash]' : ''}`,
            chunkFilename: `[id].css${isRelease ? '?[chunkhash]' : ''}`,
        }),
        new VueLoaderPlugin(),
        new Notification(),
    ],
    optimization: {
        minimize: isRelease,
        minimizer: [new TerserJSPlugin({}), new OptimizeCSSAssetsPlugin({})],
    },
    resolve: {
        alias: {
            '@img': path.resolve(__dirname, 'assets/images'),
            '@': path.resolve(__dirname, 'assets')
        }
    },
};

if (isRelease) {
    module.exports.plugins.push(new Manifest('manifest.json'));

    module.exports.plugins.push(
        new WebpackCleanPlugin({
            on: "emit",
            path: ['./dist']
        }));

    // module.exports.plugins.push(new PurifyCSSPlugin({
    //     paths: glob_all.sync([
    //         path.join(__dirname, '**!/!*.php'),
    //         path.join(__dirname, 'assets/js/!**!/!*.js'),
    //         path.join(__dirname, 'assets/vue/!**!/!*.vue'),
    //         path.join(__dirname, 'node_modules/simplebar/**/*.js'),
    //     ]),
    //     whitelist: [
    //         'rtl',
    //         'ltr'
    //     ],
    // }));
}

