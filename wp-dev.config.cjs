/* eslint-disable no-undef */
// See https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/#advanced-usage

const defaultConfig = require('@wordpress/scripts/config/webpack.config')
const BrowserSyncPlugin = require('browser-sync-webpack-plugin')
const MiniCSSExtractPlugin = require('mini-css-extract-plugin')
const path = require('path')

// remove default `MiniCSSExtractPlugin` & `RtlCssPlugin` plugins
const defaultPlugins = defaultConfig.plugins.filter(
	(p) => p.constructor?.name !== 'MiniCssExtractPlugin' && p.constructor?.name !== 'RtlCssPlugin',
)

module.exports = {
	...defaultConfig,
	entry: {
		zuplus: ['./scripts/zuplus.js', './sass/zuplus.scss'],
		'zuplus-remove-backups': ['./scripts/zuplus-remove-backups.js'],
		'zuplus-admin': './scripts/zuplus-admin.js',
		// трюк, потому что `wp-scripts` все равно будет пытаться генерить JS скрипт
		// мы потом всё удалим с помощью `CleanWebpackPlugin`
		'zuplus-debugbar': './sass/zuplus-debugbar.scss',
		'zuplus-kint': './sass/zuplus-kint.scss',
	},
	output: {
		filename: 'js/[name].min.js',
		path: path.resolve(__dirname, 'admin'),
	},
	plugins: [
		...defaultPlugins,
		new MiniCSSExtractPlugin({
			filename: 'css/[name].css',
		}),
		new BrowserSyncPlugin({
			// browse to http://localhost:3002/ during development,
			host: 'localhost',
			port: 3002,
			https: true,
			open: false,
			proxy: 'https://dr.local/wp-admin',
		}),
	],
	stats: {
		children: false,
		assets: false,
		modules: false,
	},
}
