/**
 * WordPress dependencies
 */
const { test } = require('@wordpress/e2e-test-utils-playwright');
const { LoginGoogle } = require('../page/LoginGoogle.js');

 test.describe("Disable the logout button", () => {
   test("Logout button should not be visible", async ({ page, admin }) => {
     const loginGoogleobj = new LoginGoogle(page);
     await admin.createNewPost();
     await loginGoogleobj.validateBlock();
     await loginGoogleobj.addLoginGoogleBlock();
     await loginGoogleobj.validateDisableButtonFrontend();
   });
 });
 