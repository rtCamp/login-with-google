/**
 * WordPress dependencies
 */
const { test } = require('@wordpress/e2e-test-utils-playwright');
const { LoginGoogle } = require('../page/LoginGoogle.js');

 test.describe("Check the login with google button", () => {
   test("should be able validate login with google button", async ({ page,context }) => {
     await context.clearCookies();
     const loginGoogleobj = new LoginGoogle(page);
     await loginGoogleobj.validateButtonAfterLogout();
   });
 });
 