/**
 * External dependencies
 */
const fs = require('fs');
const path = require('path');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');
const RemoveEmptyScriptsPlugin = require('webpack-remove-empty-scripts');

/**
 * WordPress dependencies
 */
const defaultConfig = require('@wordpress/scripts/config/webpack.config');

const sharedConfig = {
	...defaultConfig,
	output: {
		path: path.resolve(process.cwd(), 'assets', 'js'),
		filename: '[name].js',
		chunkFilename: '[name].js',
	},
	plugins: [
		...defaultConfig.plugins
			.map((plugin) => {
				if (plugin.constructor.name === 'MiniCssExtractPlugin') {
					plugin.options.filename = '../css/[name].css';
				}
				return plugin;
			})
			.filter(
				(plugin) => plugin.constructor.name !== 'CleanWebpackPlugin'
			),
		new RemoveEmptyScriptsPlugin(),
	],
	optimization: {
		...defaultConfig.optimization,
		splitChunks: {
			...defaultConfig.optimization.splitChunks,
			cacheGroups: {
				...defaultConfig.optimization.splitChunks.cacheGroups,
				// Disable `style` cache group from default config.
				style: false,
			},
		},
		minimizer: defaultConfig.optimization.minimizer.concat([
			new CssMinimizerPlugin(),
		]),
	},
};

const blockButtonJs = {
	...sharedConfig,
	entry: {
		'block-button': './assets/src/js/block-button.js',
	},
};

const loginJs = {
	...sharedConfig,
	entry: {
		login: './assets/src/js/login.js',
	},
};

const onetapJs = {
	...sharedConfig,
	entry: {
		onetap: './assets/src/js/onetap.js',
	},
};

const styles = {
	...sharedConfig,
	entry: () => {
		const entries = {};

		const dir = './assets/src/scss';
		fs.readdirSync(dir).forEach((fileName) => {
			const fullPath = `${dir}/${fileName}`;
			if (!fs.lstatSync(fullPath).isDirectory()) {
				entries[fileName.replace(/\.[^/.]+$/, '')] = fullPath;
			}
		});

		return entries;
	},
	module: {
		...sharedConfig.module,
		rules: sharedConfig.module.rules.map((rule) => {
			const cssLoader =
				Array.isArray(rule.use) &&
				rule.use.find(
					(loader) =>
						loader.loader && loader.loader.includes('/css-loader')
				);

			/**
			 * Prevent "Module not found: Error: Can't resolve ..."
			 * being thrown for `url()` CSS rules.
			 */
			if (cssLoader) {
				cssLoader.options = {
					...cssLoader.options,
					url: false,
				};
			}

			return rule;
		}),
	},
	plugins: [
		...sharedConfig.plugins.filter(
			(plugin) =>
				plugin.constructor.name !== 'DependencyExtractionWebpackPlugin'
		),
	],
};

const copyAssets = {
	...sharedConfig,
	entry: {},
	plugins: [
		new CopyWebpackPlugin({
			patterns: [
				{
					from: './assets/src/images',
					to: '../images',
				},
			],
		}),
	],
};

module.exports = [loginJs, onetapJs, blockButtonJs, styles, copyAssets];
