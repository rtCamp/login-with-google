/**
 * WordPress dependencies
 */
const { test } = require('@wordpress/e2e-test-utils-playwright');
const { LoginGoogle } = require('../page/LoginGoogle.js');

 test.describe("Add login with google block ", () => {
   test("Should be able to add the login with google block", async ({ page, admin }) => {
     const loginGoogleobj = new LoginGoogle(page);
     await admin.createNewPost();
     await loginGoogleobj.validateBlock();
     await loginGoogleobj.addLoginGoogleBlock();
   });
 });
 