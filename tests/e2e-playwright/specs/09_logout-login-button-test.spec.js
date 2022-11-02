/**
 * WordPress dependencies
 */
const { test, expect } = require("@wordpress/e2e-test-utils-playwright");
const { chromium } = require("@playwright/test");
const {
  WP_BASE_URL,
  WP_USERNAME,
  WP_PASSWORD
} = require( '../e2e-test-utils-playwright/src/config' );

test.describe("Should be able to logout and login from the added gutenberg block", () => {
  

  test("Should be able to logout", async ({ page, admin }) => {

    await page.goto(WP_BASE_URL + '/wp-login.php' );
    await page.waitForTimeout(1000);
    await page.type( '#user_login', WP_USERNAME )
    await page.type( '#user_pass', WP_PASSWORD )

    await page.click( '#wp-submit' );

    await admin.createNewPost();

    await page.type("h1[aria-label='Add title']", "Test block");

    await page.click('role=button[name="Add block"i]');

    await page.type("#components-search-control-0", "Login with google");

    expect(page.locator("role=option[name=/Log in with Google/i]")).not.toBe(
      null
    );

    await page.click("role=option[name=/Log in with Google/i]");

    await page.type(
      "div[aria-label='Log in with Google']",
      "Login with google"
    );

    await page.click('role=checkbox[name="Display Logout"i]');

    await page.click(
      ".components-button.block-editor-post-preview__button-toggle.components-dropdown-menu__toggle.is-tertiary"
    );

    //Click on publish button
    await page.click(".editor-post-publish-panel__toggle");

    //Double check, click again on publish button
    await page.click(".editor-post-publish-button");

    await page.click('[aria-label="Editor publish"] >> text=View Post');

    await page.waitForTimeout(1000);

    await page.click(".wp_google_login__button");

    await page.waitForTimeout(1000);
  });

  test("Should be able to login", async () => {
    const browser = await chromium.launch({
      headless: true,
      args: [
        "--disable-site-isolation-trials",
        "--disable-features=site-per-process,SitePerProcess",
        "--disable-blink-features=AutomationControlled",
      ],
    });

    const ctx = await browser.newContext();
    const page = await ctx.newPage();

    await page.goto(WP_BASE_URL);

    await page.locator( 'role=link[name="Test block"i]' ).first().click();

    await page.click(".wp_google_login__button");

    await page.waitForTimeout(1000);
    await page.type('input[type="email"]', process.env.EMAIL);
    await page.click("#identifierNext");
    await page.waitForSelector('input[type="password"]', { visible: true });
    await page.type('input[type="password"]',process.env.PASSWORD);
    await page.waitForSelector("#passwordNext", { visible: true });
    await page.click("#passwordNext");

    await page.waitForTimeout(4000);
    expect(page.locator(".entry-title")).toHaveText(
      "Test block."
    );
  });
});
