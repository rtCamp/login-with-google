<p align="center">
<a href="https://rtcamp.com/?ref=wp-menu-custom-fields-repo" target="_blank"><img width="200"src="https://rtcamp.com/wp-content/themes/rtcamp-v9/assets/img/site-logo-black.svg"></a>
</p>
# WP Google Login
[![Project Status: Active – The project has reached a stable, usable state and is being actively developed.](https://www.repostatus.org/badges/latest/active.svg)](https://www.repostatus.org/#active)

Minimal plugin which allows WP user to login with google. This plugin can be used as MU plugin too.

**Author:** rtCamp

**Tags:** Google login

**Requires at least:** 5.0

**Tested up to:** 5.4

**Requires PHP version:** 7.0

**Stable tag:** 1.0

**License:** GPLv2 or later (of course!)

**License URI:** http://www.gnu.org/licenses/gpl-2.0.html

#### How to use it?

1. If you're cloning repo then after cloning run `composer install --no-dev` to install dependencies. GitHub release zip and WordPress.org download doesn't need this step.
2. Create project from [Google console](https://console.developers.google.com/apis/dashboard) if not exists.
3. Go to **Credentials** tab, And create credential for OAuth client.
    * Application type will be **Web application**
    * Add `YOUR_DOMAIN/wp-login.php` in **Authorized redirect URIs**
4. This will give you Client ID and Secret key.
5. You can use below snippet, replace value of const with client id and secret. And add that in to `wp-config.php` 

```php
define( 'WP_GOOGLE_LOGIN_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID' );
define( 'WP_GOOGLE_LOGIN_SECRET', 'YOUR_SECRET_KEY' );
```
Or you can set these from `Settings > WP Google Login` page from wp-admin

#### How to enable user registration?
You can enable user registration either by
- Checking `Settings > WP Google Login > Enable Google Login Registration`
OR
- Adding `define( 'WP_GOOGLE_LOGIN_USER_REGISTRATION', 'true' );` in wp-config.php file.

Note: If the checkbox is ON then, it will register user even when WordPress default setting, under `Settings > General Settings > Membership > Anyone can register` checkbox is OFF.

#### How to restrict user registration to one or more domain(s)?

By default, when you enable user registration via constant `WP_GOOGLE_LOGIN_USER_REGISTRATION` or enable `Settings > WP Google Login > Enable Google Login Registration`, it will create a user for any Google login (including gmail.com users). If you are planning to use this plugin on a private, internal site, then you may like to restrict user registration to users under Google Suite organization. This configuration variable does that.

Add your domain name, without any schema prefix and `www,` as the value of `WP_GOOGLE_LOGIN_WHITELIST_DOMAINS` const or in the settings `Settings > WP Google Login > Whitelisted Domains`. You can whitelist multiple domains. Please separate domains with commas. See the below example to know how to do it via constants:

**Note:** If a user already exists then it will allow a user to login with Google regardless of its domain is whitelisted or not. It will only prevent the user from registering with an email address with a domain that not whitelisted.
 
```php
define( 'WP_GOOGLE_LOGIN_WHITELIST_DOMAINS', 'example.com, sample.com' );
```


### Hooks

#### 1. Action `wp_google_login_token`
This action provides access token received after Google login.  
**Parameters:**
* `token` (Array): Converted token using `fetchAccessTokenWithAuthCode` method of `Google_Client` class.
* `user_info` (Array): Details of user after login.
* `client` (Object): `Google_Client` object in use.

#### 2. Filter `wp_google_login_scopes`
This filter can be used to filter existing scope used in Google Sign in.
You can ask for additional permission while user logs in.

This filter will provide 1 parameter `scopes` in callback, which contains array of scopes.


## wp-config.php parameters list

|                                   | Type    | Description                                                                                                                                                                 |
|-----------------------------------|---------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| WP_GOOGLE_LOGIN_CLIENT_ID         | String  | Google client ID of your application.                                                                                                                                       |
| WP_GOOGLE_LOGIN_SECRET            | String  | Secret key of your application                                                                                                                                              |
| WP_GOOGLE_LOGIN_USER_REGISTRATION | Boolean | (Optional) Set True If you want to enable new user registration. By default, user registration defers to `Settings > General Settings > Membership` if constant is not set. |
| WP_GOOGLE_LOGIN_WHITELIST_DOMAINS | String  | (Optional) Domain name, if you want to restrict login with your custom domain. By default, It will allow all domains. You can whitelist multiple domains.                   |


## Contribute

### Reporting a bug 🐞

Before creating a new issue, do browse through the [existing issues](https://github.com/rtCamp/wp-google-login/issues) for resolution or upcoming fixes. 

If you still need to [log an issue](https://github.com/rtCamp/wp-google-login/issues/new), making sure to include as much detail as you can, including clear steps to reproduce your issue if possible.

### Creating a pull request

Want to contribute a new feature? Start a conversation by logging an [issue](https://github.com/rtCamp/wp-google-login/issues).

Once you're ready to send a pull request, please run through the following checklist: 

1. Browse through the [existing issues](https://github.com/rtCamp/wp-google-login/issues) for anything related to what you want to work on. If you don't find any related issues, open a new one.

1. Fork this repository.

1. Create a branch from `develop` for each issue you'd like to address and commit your changes.

1. Push the code changes from your local clone to your fork.

1. Open a pull request and that's it! We'll with feedback as soon as possible (Isn't collaboration a great thing? 😌)

1. Once your pull request has passed final code review and tests, it will be merged into `develop` and be in the pipeline for the next release. Props to you! 🎉

## Unit testing

- Setup local unit test environment by running script from terminal

```./bin/install-wp-tests.sh <db-name> <db-user> <db-pass> [db-host] [wp-version] [skip-database-creation]```

- Execute `phpunit` in terminal from repository to run all test cases.

- Execute `phpunit ./tests/inc/test-class.php` in terminal with file path to run specific tests.


# BTW, We're Hiring!

<a href="https://rtcamp.com/"><img src="https://rtcamp.com/wp-content/uploads/2019/04/github-banner@2x.png" alt="Join us at rtCamp, we specialize in providing high performance enterprise WordPress solutions"></a>
