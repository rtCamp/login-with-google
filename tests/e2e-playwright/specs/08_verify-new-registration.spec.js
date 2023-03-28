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


test.describe("Verify the new user registration ", () => {
  test("Disable newuser registration", async ({ page, admin }) => {

    await page.goto(WP_BASE_URL + '/wp-login.php' );

    await page.waitForTimeout(2000);

    await page.type( '#user_login', WP_USERNAME )
    await page.type( '#user_pass', WP_PASSWORD )

    await page.click( '#wp-submit' );

    await admin.visitAdminPage("/");

    await page.hover('role=link[name="Settings"i]');

    await page.click('role=link[name="Login with Google"i]');

    await page.waitForTimeout(1000);
    expect(page.locator("form[action='options.php'] h2")).toHaveText(
      "Log in with Google Settings"
    );

    await page.locator("#user-registration").uncheck();

    expect(await page.locator("#user-registration").isChecked()).toBeFalsy();

     // Hover on profile icon.
     await page.hover( '#wp-admin-bar-my-account' );
 
     // Click on logout button. 
     await page.click( '#wp-admin-bar-logout' );

    
  });

  test("Should be able to verify the unallowed registration message", async () => {
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

    await page.goto(WP_BASE_URL + '/wp-login.php' );

    await page.click(".wp_google_login__button");

    await page.waitForTimeout(1000);
    await page.type('input[type="email"]', process.env.GMAIL_UNAME);
    await page.click("#identifierNext");
    await page.screenshot({ path: 'uploads/screenshot.png', fullPage: true  });  
    await page.waitForSelector('input[type="password"]', { visible: true });
    await page.type('input[type="password"]', process.env.GMAIL_PASS);
    await page.waitForSelector("#passwordNext", { visible: true });
    await page.click("#passwordNext");

    await page.waitForTimeout(4000);
    expect(page.locator("#login_error")).toHaveText(
      "Registration is not allowed."
    );

  });
});
