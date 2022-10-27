const WP_ADMIN_USER = {
	username: 'cool-wilson',
	password: 'GOEZZtOOwpZsxrhPTz',
} as const;

const {
	WP_USERNAME = WP_ADMIN_USER.username,
	WP_PASSWORD = WP_ADMIN_USER.password,
	WP_BASE_URL = 'https://qa-team.rt.gw/',
} = process.env;

export { WP_ADMIN_USER, WP_USERNAME, WP_PASSWORD, WP_BASE_URL };
