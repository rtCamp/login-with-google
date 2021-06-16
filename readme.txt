=== Log in with Google ===
Contributors: rtCamp
Donate link: https://rtcamp.com/
Tags: Google login, sign in, sso, oauth, authentication, sign-in, single sign-on, log in
Requires at least: 5.0
Tested up to: 5.7
Requires PHP: 7.0
Stable tag: 1.0.14
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
 
Minimal plugin that allows WordPress users to log in using Google.
 
== Description ==

Minimal plugin that allows WordPress users to log in using Google.

= Setup =

1. Create a project from [Google Developers Console](https://console.developers.google.com/apis/dashboard) if none exists.
2. Go to **Credentials** tab, then create credential for OAuth client.
    * Application type will be **Web Application**
    * Add `YOUR_DOMAIN/wp-login.php` in **Authorized redirect URIs**
3. This will give you **Client ID** and **Secret key**.
4. Input these values either in `WP Admin > Settings > WP Google Login`, or in `wp-config.php` using the following code snippet:

`
define( 'WP_GOOGLE_LOGIN_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID' );
define( 'WP_GOOGLE_LOGIN_SECRET', 'YOUR_SECRET_KEY' );
`

= How to enable automatic user registration =

You can enable user registration either by
- Checking `Settings > WP Google Login > Enable Google Login Registration`
OR
- Adding `define( 'WP_GOOGLE_LOGIN_USER_REGISTRATION', 'true' );` in wp-config.php file.

Note: If the checkbox is ON then, it will register valid Google users even when WordPress default setting, under `Settings > General Settings > Membership > Anyone can register` checkbox is OFF.

= How to restrict user registration to one or more domain(s) =

By default, when you enable user registration via constant `WP_GOOGLE_LOGIN_USER_REGISTRATION` or enable `Settings > WP Google Login > Enable Google Login Registration`, it will create a user for any Google login (including gmail.com users). If you are planning to use this plugin on a private, internal site, then you may like to restrict user registration to users under a single Google Suite organization. This configuration variable does that.

Add your domain name, without any schema prefix and `www,` as the value of `WP_GOOGLE_LOGIN_WHITELIST_DOMAINS` constant or in the settings `Settings > WP Google Login > Whitelisted Domains`. You can whitelist multiple domains. Please separate domains with commas. See the below example to know how to do it via constants:

`define( 'WP_GOOGLE_LOGIN_WHITELIST_DOMAINS', 'example.com,sample.com' );`

**Note:** If a user already exists, they **will be allowed to login with Google** regardless of whether their domain is whitelisted or not. Whitelisting will only prevent users from **registering** with email addresses from non-whitelisted domains.

= Hooks =

Action `wp_google_login_token`
This action provides access token received after Google login.  
**Parameters:**

* `token` (Array): Converted token using `fetchAccessTokenWithAuthCode` method of `Google_Client` class.
* `user_info` (Array): Details of user after login.
* `client` (Object): `Google_Client` object in use.

Filter `wp_google_login_scopes`
This filter can be used to filter existing scope used in Google Sign in.
You can ask for additional permission while user logs in.
This filter will provide 1 parameter `scopes` in callback, which contains array of scopes.

= wp-config.php parameters list =

* `WP_GOOGLE_LOGIN_CLIENT_ID` (string): Google client ID of your application.
* `WP_GOOGLE_LOGIN_SECRET` (string): Secret key of your application
* `WP_GOOGLE_LOGIN_USER_REGISTRATION` (boolean) (optional): Set `true` If you want to enable new user registration. By default, user registration defers to `Settings > General Settings > Membership` if constant is not set.
* `WP_GOOGLE_LOGIN_WHITELIST_DOMAINS` (string) (optional): Domain names, if you want to restrict login with your custom domain. By default, it will allow all domains. You can whitelist multiple domains.

= BTW, We're Hiring! =

[Join us at rtCamp, we specialize in providing high performance enterprise WordPress solutions](https://rtcamp.com/)

== Installation ==
 
1. Upload `plugin-name.php` to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Follow "Setup" instructions in ReadMe to configure credentials from Google Developers Console.
 
== Frequently Asked Questions ==
 
= Reporting a bug 🐞 =
 
Before creating a new issue, do browse through the [existing issues](https://github.com/rtCamp/login-with-google/issues) for resolution or upcoming fixes. 

If you still need to [log an issue](https://github.com/rtCamp/login-with-google/issues/new), making sure to include as much detail as you can, including clear steps to reproduce the issue, if possible.
 
= Creating a pull request =
 
Want to contribute a new feature? Start a conversation by [logging an issue](https://github.com/rtCamp/login-with-google/issues).

Once you're ready to send a pull request, please run through the following checklist: 

1. Browse through the [existing issues](https://github.com/rtCamp/login-with-google/issues) for anything related to what you want to work on. If you don't find any related issues, open a new one.

2. Fork this repository.

3. Create a branch from `develop` for each issue you'd like to address and commit your changes.

4. Push the code changes from your local clone to your fork.

5. Open a pull request and that's it! We'll respond with feedback as soon as possible (Isn't collaboration a great thing? 😌)

6. Once your pull request has passed final code review and tests, it will be merged into `develop` and be in the pipeline for the next release. Props to you! 🎉

= Unit testing =

- Setup local unit test environment by running script from terminal

`./bin/install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version] [skip-database-creation]`

- Execute `phpunit` in terminal from repository to run all test cases.

- Execute `phpunit ./tests/inc/test-class.php` in terminal with file path to run specific tests.
 
== Screenshots ==
 
1. Login screen with Google option added.
2. Plugin settings screen.
3. Settings within Google Developer Console.
 
== Changelog ==

= 1.0.14 =
* Revert Login with GitHub state fix.

= 1.0.13 =
* Fix login issue related to oAuth state.

= 1.0.12 =
* Fix conflict with Login with GitHub plugin.

= 1.0.11 =
* Add 'login_with_google/client_arguments' filter for Google_Client arguments.

= 1.0.10 =
* Fix issue where JS/CSS were not loading.

= 1.0.9 =
* Initial release.
 
== Upgrade Notice ==

= 1.0.10 =
* Fix issue where JS/CSS were not loding.
