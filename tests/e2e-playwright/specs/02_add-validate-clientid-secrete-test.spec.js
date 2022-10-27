/**
 * WordPress dependencies
 */
const { test, expect } = require("@wordpress/e2e-test-utils-playwright");

test.describe("Add Client ID and secrete in settings", () => {
  test("should be able to add the ID and secrete", async ({ page, admin }) => {
    await admin.visitAdminPage("/");

    await page.hover('role=link[name="Settings"i]');

    await page.click('role=link[name="Login with Google"i]');

    expect(page.locator("form[action='options.php'] h2")).toHaveText(
      "Log in with Google Settings"
    );

    await page.focus("#client-id");
    await page.keyboard.down("Control");
    await page.keyboard.press("A");
    await page.keyboard.up("Control");
    await page.keyboard.press("Backspace");
    await page.type(
      "#client-id",
      "278082871881-qp9srgor0iqvl1aq200ctf1sdb49bli1.apps.googleusercontent.com"
    );

    await page.focus("#client-secret");
    await page.keyboard.down("Control");
    await page.keyboard.press("A");
    await page.keyboard.up("Control");
    await page.keyboard.press("Backspace");
    await page.type("#client-secret", "Y3O4gbY4JnateqAj10GxL_6t");

    await page.click("#submit");
  });
});
