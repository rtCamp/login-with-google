/**
 * WordPress dependencies
 */
const { test, expect } = require("@wordpress/e2e-test-utils-playwright");

test.describe("Validate the login with google settings", () => {
  test("should be able to validate the settings", async ({ page, admin }) => {
    await admin.visitAdminPage("/plugins.php");


    await page.screenshot({path: "uploads/img.png", fullPage: true});

    await page.hover('role=link[name="Settings"i]');

    await page.click('role=link[name="Login with Google"i]');

    await page.waitForTimeout(1000);
    expect(page.locator("form[action='options.php'] h2")).toHaveText(
      "Log in with Google Settings"
    );
  });
});
