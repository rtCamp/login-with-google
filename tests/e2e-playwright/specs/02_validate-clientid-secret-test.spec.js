/**
 * WordPress dependencies
 */
const { test, expect } = require('@wordpress/e2e-test-utils-playwright');
const { LoginGoogle } = require('../page/LoginGoogle.js');
const { selectors } = require('../utils/selectors.js');

test.describe('Add Client ID and secrete in settings', () => {
  test('should be able to add the ID and secrete', async ({ page }) => {
    const loginGoogleobj = new LoginGoogle(page);
    await loginGoogleobj.navigateToSettings();
    await loginGoogleobj.validateHeading();
    await loginGoogleobj.setIdSecret();
    await loginGoogleobj.saveSetting();
  })
})
