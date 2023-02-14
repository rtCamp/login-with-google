/**
 * WordPress dependencies
 */
 const { test, expect } = require("@wordpress/e2e-test-utils-playwright");

 test.describe("Disable the logout button", () => {
   test("Logout button should not be visible", async ({ page, admin }) => {
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
 
     await page.locator('role=checkbox[name="Display Logout"i]').uncheck();
 
     await page.click(
       ".components-button.block-editor-post-preview__button-toggle.components-dropdown-menu__toggle.is-tertiary"
     );
 
     //Click on publish button
     await page.click(".editor-post-publish-panel__toggle");
 
     //Double check, click again on publish button
     await page.click(".editor-post-publish-button");
 
     await page.click('[aria-label="Editor publish"] >> text=View Post');
 
     await page.waitForTimeout(1000);

     expect(page.locator(".wp_google_login__button")).not.toBeVisible();
   });
 });
 