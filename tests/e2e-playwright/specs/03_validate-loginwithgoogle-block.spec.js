/**
 * WordPress dependencies
 */
const { test } = require("@wordpress/e2e-test-utils-playwright");
const { LoginGoogle } = require('../page/LoginGoogle.js');

test.describe("Validate the login with google block ", () => {
  test("Login with google block should be present", async ({ page, admin }) => {
    const loginGoogleobj = new LoginGoogle(page);
    await admin.createNewPost();
    await loginGoogleobj.validateBlock();
  });
});
