#!/usr/bin/env node

/**
 * Internal dependencies
 */
const fs = require('fs');
const path = require('path');

const buildFiles = ['assets/js', 'assets/css', 'assets/images'];

buildFiles.forEach((dir) => {
	const buildDir = path.resolve(process.cwd(), dir);

	try {
		if (fs.existsSync(buildDir)) {
			fs.rm(buildDir, { recursive: true }, (err) => {
				if (err) {
					throw err;
				}
			});
		}
	} catch (error) {
		// eslint-disable-next-line no-console
		console.error(error);
	}
});
