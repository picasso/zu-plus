/* eslint-disable no-undef */
// See https://developer.wordpress.org/block-editor/reference-guides/packages/packages-scripts/#advanced-usage

const defaultConfig = require('@wordpress/scripts/config/webpack.config')
const { CleanWebpackPlugin } = require('clean-webpack-plugin')
const MiniCSSExtractPlugin = require('mini-css-extract-plugin')
const path = require('path')

// defaultConfig.plugins.forEach((value, index) => {
// 	console.log(index + ': [' + value.constructor?.name + ']')
// 	console.log(value)
// })

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
		new CleanWebpackPlugin({
			protectWebpackAssets: false,
			cleanOnceBeforeBuildPatterns: ['**/*', '!*.js'],
			cleanAfterEveryBuildPatterns: [
				'**/*asset.php',
				'**/*-debugbar.min.js',
				'**/*-kint.min.js',
			],
		}),
	],
	stats: {
		children: false,
		assets: false,
		modules: false,
	},
}
