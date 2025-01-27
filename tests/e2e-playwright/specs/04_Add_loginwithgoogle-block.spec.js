/**
 * WordPress dependencies
 */
 const { test, expect } = require("@wordpress/e2e-test-utils-playwright");

 test.describe("Add login with google block ", () => {
   test("Should be able to add the login with google block", async ({ page, admin }) => {
     await admin.createNewPost();
 
     await page.type("h1[aria-label='Add title']", "Test block");
 
     await page.click('role=button[name="Add block"i]');
 
     await page.type("#components-search-control-0", "Login with google");
 
     expect(page.locator("role=option[name=/Log in with Google/i]")).not.toBe(
       null
     );

    await page.click( "role=option[name=/Log in with Google/i]" );

    await page.type( "div[aria-label='Log in with Google']", "Login with google" );

   });

   
 });
 