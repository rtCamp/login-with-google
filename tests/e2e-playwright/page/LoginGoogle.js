const { expect } = require("@playwright/test");
const { selectors } = require('../utils/selectors')
const {
    WP_BASE_URL,
    WP_USERNAME,
    WP_PASSWORD
} = require('../e2e-test-utils-playwright/src/config');

exports.LoginGoogle = class LoginGoogle {
    constructor(page) {
        this.page = page;  
    }

// this function is used to navigate to Settings page
async navigateToSettings(){
    await this.page.goto("./wp-admin/options-general.php?page=login-with-google", { waitUntil: "load" });
}
// this function is used to validate setting's page Heading
async validateHeading(){
  await  expect(this.page.locator(selectors.settingsHeader)).toHaveText(selectors.settingsHeading);
}
// this functions is used to save settings
async saveSetting() {
 await this.page.locator(selectors.buttonSaveSetting).click();
 expect(this.page.locator(selectors.messageSaveSetting)).not.toBeNull();
}
// this function is used to add proper secret and ID
async setIdSecret(){
    await this.page.click(selectors.clientIdInput, { clickCount: 3 })
    await this.page.keyboard.press('Backspace')
    await this.page.type(selectors.clientIdInput,selectors.clientIdValue)
    await this.page.waitForTimeout(1000)
    await this.page.click(selectors.clientSecretInput, { clickCount: 3 })
    await this.page.keyboard.press('Backspace')
    await this.page.type(selectors.clientSecretInput,selectors.clientSecretValue)
}
// this function is used to validate Login with google block is present in the editor
async validateBlock(){
    await this.page.type(selectors.titleInput,selectors.titleValue);
    await this.page.click(selectors.addBlockButton);
    await this.page.type(selectors.searchInput,selectors.searchInputValue);
    expect(this.page.locator(selectors.validateOption)).not.toBe(null);
}
// this function is used to add Login with google block after validate Block
async addLoginGoogleBlock(){
    await this.page.click(selectors.validateOption);
    await this.page.type(selectors.loginGoogleDiv,selectors.searchInputValue);
}
// this function is used to validate login with google component on the frontend after adding the google block
async validateLoginBlockFrontend(){
    await this.page.click(selectors.logoutCheckbox);
    await this.page.click(selectors.publishPanel);
    await this.page.click(selectors.publishButton);
    await this.page.focus(selectors.viewPostButton);
    await this.page.locator(selectors.viewPostButton).click();
    await this.page.waitForTimeout(1000);
    await expect(this.page.locator(selectors.frontendLoginButton)).toHaveText("Log out");
}
// this function is used to validate disable button on the frontend
async validateDisableButtonFrontend(){
    await this.page.locator(selectors.logoutCheckbox).uncheck();
    await this.page.click(selectors.publishPanel);
    await this.page.click(selectors.publishButton);
    await this.page.click(selectors.viewPostButton);
    await this.page.waitForTimeout(1000);
    await expect(this.page.locator(selectors.frontendLoginButton)).toBeHidden();
}
// this function is used for logout
async logOut(){
    await this.page.hover(selectors.wpAdminBar); 
    await this.page.click(selectors.wpLogout);
}
// this function is used to validate login with google button after logout 
async validateButtonAfterLogout(){
    await this.page.goto('/wp-admin/', { waitUntil: "load" });
    await this.page.waitForTimeout(1000);
    expect(this.page.locator(selectors.wpGoogleLogin)).not.toBe(null);
}
// this function is used to disable new user registration
async disableUserReg(){
    await this.page.locator(selectors.userRegistration).uncheck();
    expect(await this.page.locator(selectors.userRegistration).isChecked()).toBeFalsy();
}





}