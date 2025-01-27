/**
 * WordPress dependencies
 */
 const { test, expect } = require("@wordpress/e2e-test-utils-playwright");

 test.describe("Check the login with google button", () => {

   test("should be able validate login with google button", async ({ page, admin }) => {
     await admin.visitAdminPage("/");

       // Hover on profile icon.
       await page.hover( '#wp-admin-bar-my-account' );
 
       // Click on logout button. 
       await page.click( '#wp-admin-bar-logout' );

       await page.waitForTimeout(1000);
       expect(page.locator( '.message' )).toHaveText( 'You are now logged out.' );

       expect( page.locator( '.wp_google_login' ) ).not.toBe(null);
 

   });
 });
 