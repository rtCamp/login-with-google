/**
 * WordPress dependencies
 */
const { test, expect } = require("@wordpress/e2e-test-utils-playwright");

test.describe("Validate the login with google block ", () => {
  test("Login with google block should be present", async ({ page, admin }) => {
    await admin.createNewPost();

    await page.type("h1[aria-label='Add title']", "Test block");

    await page.click('role=button[name="Add block"i]');

    await page.type("#components-search-control-0", "Login with google");

    expect(page.locator("role=option[name=/Log in with Google/i]")).not.toBe(
      null
    );
  });
});
