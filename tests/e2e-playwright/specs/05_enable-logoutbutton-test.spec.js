/**
 * WordPress dependencies
 */
const { test } = require('@wordpress/e2e-test-utils-playwright');
const { LoginGoogle } = require('../page/LoginGoogle.js');

test.describe("Enable the logout button", () => {
  test("Should be able to view the logout button on the frontend", async ({ page, admin }) => {
    const loginGoogleobj = new LoginGoogle(page);
    await admin.createNewPost();
    await loginGoogleobj.validateBlock();
    await loginGoogleobj.addLoginGoogleBlock();
    await loginGoogleobj.validateLoginBlockFrontend();
  });
});
