/**
 * WordPress dependencies
 */
const defaultConfig = require('@wordpress/babel-preset-default');

module.exports = function (api) {
	const config = defaultConfig(api);

	// Keep config extensible in case we need to add more plugins.
	return {
		...config,
		sourceMaps: true,
	};
};
