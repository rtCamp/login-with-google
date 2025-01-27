/**
 * WordPress dependencies
 */
const { test } = require('@wordpress/e2e-test-utils-playwright');
const { LoginGoogle } = require('../page/LoginGoogle.js');
const { selectors } = require('../utils/selectors.js');

test.describe("Verify the new user registration ", () => {
  test("Disable newuser registration", async ({ page, admin }) => {
    const loginGoogleobj = new LoginGoogle(page);
    await loginGoogleobj.navigateToSettings();
    await loginGoogleobj.validateHeading();
    await loginGoogleobj.disableUserReg();
    await loginGoogleobj.saveSetting();
  });
});
