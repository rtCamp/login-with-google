/**
 * WordPress dependencies
 */
const { test, expect } = require('@wordpress/e2e-test-utils-playwright')

test.describe('Set cookie expiry field in the settings', () => {
	test('should be able to set cookie expiry field', async ({ page, admin }) => {
		await admin.visitAdminPage('/')

		await page.hover('role=link[name="Settings"i]')

		await page.click('role=link[name="Login with Google"i]')

		expect(page.locator("form[action='options.php'] h2")).toHaveText(
		  'Log in with Google Settings'
		)

		await page.type(
		  '#cookie-expiry',
		  '320'
		)

		await page.waitForTimeout(1000)

		await page.click('#submit')
	})
})
