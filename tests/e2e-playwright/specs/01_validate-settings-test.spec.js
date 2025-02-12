/**
 * WordPress dependencies
 */
const { test } = require('@wordpress/e2e-test-utils-playwright');
const { LoginGoogle } = require('../page/LoginGoogle.js');

test.describe("Validate the login with google settings", () => {
  test("should be able to validate the settings", async ({ page, admin }) => {
    const loginGoogleobj = new LoginGoogle(page);
    await admin.visitAdminPage("/");
    await loginGoogleobj.navigateToSettings();
    await loginGoogleobj.validateHeading();
  });
});
