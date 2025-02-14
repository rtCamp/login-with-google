# Login with Google

> WordPress plugin to login/register with google

- [Overview](#overview)
- [Installation](#installation)
- [Browser support](#browser-support)
- [Usage Instructions](#usage-instructions)
- [Plugin Constants](#plugin-constants)
- [Hooks](#hooks)
    - [Filters](#filters)
    - [Actions](#actions)
- [Shortcode](#shortcode)
- [Contribute](#contribute)
- [Unit testing](#unit-testing)
- [Minimum Requirements](#minimum-requirements)
- [License](#license)
- [BTW, We're Hiring!](#btw-were-hiring)

## Overview

Login with google provides seamless experience for users to login in to WordPress 
sites using their google account. No need to manually create accounts, no need to remember quirky
passwords. Just one click and land into the site!

## Installation

1. Clone this repository.
2. Run `composer install --no-dev` from inside the cloned directory.
3. Upload the directory to `wp-content/plugins` directory.
4. Activate the plugin from WordPress dashboard.

## Browser support
[These browsers are supported](https://developers.google.com/identity/gsi/web/guides/supported-browsers). Note, for example, that One Tap Login is not supported in Safari.

## Usage Instructions

1. You will need to register a new application at https://console.cloud.google.com/apis/dashboard

2. `Authorization callback URL` should be like `https://yourdomain.com/wp-login.php`, where
`https://yourdomain.com` will be replaced by your site URL.

3. Once you create the app, you will receive the `Client ID` and `Client Secret`, add these credentials
in `Settings > Login with google` settings page in their respective fields.

4. `Create new user` enables new user registration irrespective of `Membership` settings in 
   `Settings > General`; as sometimes enabling user registration can lead to lots of spam users.
   Plugin will take this setting as first priority and membership setting as second priority, so if
   any one of them is enabled, new users will be registered by this plugin after successful authorization.

5. `Whitelisted Domains` allows users from specific domains (domain in email) to get registered on site.
This will prevent unwanted registration on website. 
**For Example:** If you want users only from your organization (`myorg.com`) to get registered on the 
website, you enter `myorg.com` in whitelisted domains. Users with google 
email like `abc@myorg.com` will be able to register on website. Contrary to this, users with emails like
`something@gmail.com` would not be able to register here.

### Plugin Constants

Above mentioned settings can also be configured via PHP constants by defining them in wp-config.php
file.

Refer following list of constants.

|                                   | Type    | Description                                                                                                                                                                 |
|-----------------------------------|---------|-----------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| WP_GOOGLE_LOGIN_CLIENT_ID         | String  | Google client ID of your application.                                                                                                                                       |
| WP_GOOGLE_LOGIN_SECRET            | String  | Secret key of your application                                                                                                                                              |
| WP_GOOGLE_LOGIN_USER_REGISTRATION | Boolean | (Optional) Set True If you want to enable new user registration. By default, user registration defers to `Settings > General Settings > Membership` if constant is not set. |
| WP_GOOGLE_LOGIN_WHITELIST_DOMAINS | String  | (Optional) Domain name, if you want to restrict login with your custom domain. By default, It will allow all domains. You can whitelist multiple domains.                   |

These constants can also be configured
via [wp-cli](https://developer.wordpress.org/cli/commands/config/).

**Note:** If you have defined the constant in wp-config.php file, corresponding settings field will be disable 
(locked for editing) on the settings page.

### Hooks
#### Filters

| Filter | Description | Parameters |
| --- | ----------- | --- |
| `rtcamp.google_scope` | This filter can be used to filter existing scope used in Google Sign in. <br />You can ask for additional permission while user logs in. | <ul><li>`scope` - contains array of scopes.</li></ul>
| `rtcamp.google_login_modules` | Filter out active modules before modules are initialized. | <ul><li>`active_modules` - contains array of active modules.</li></ul>
| `rtcamp.google_login_button_display` | This filter is useful where we want to forcefully display login button, even when user is already logged-in in system. | <ul><li>`display` - contains a boolean value of whether to display the button or not.</li></ul>
| `rtcamp.google_default_redirect` | Filter the default redirect URL in case redirect_to param is not available. <br />Default to admin URL. | <ul><li>`admin_url` - contains the admin URL address which is used as redirect URL by default.</li></ul>
| `rtcamp.google_register_user` | Check if we need to register the user. | <ul><li>`user` - contains the user object from google.</li></ul>
| `rtcamp.google_client_args` | Filter the arguments for sending in query. <br />This is useful in cases for example: choosing the correct prompt. | <ul><li>`client_args` - contains the list of query arguments to send to Google OAuth.</li></ul>
| `rtcamp.google_login_state` | Filters the state to pass to the Google API. | <ul><li>`state_data` - contains the default state data.</li></ul>
| `rtcamp.default_algorithm` | Filters default algorithm for openssl signature verification | <ul><li>`default_algo` - Default algorithm.</li><li>`algo` - Algorithm from JWT header.</li></ul>

#### Actions

| Action | Description | Parameters |
| --- | ----------- | --- |
| `rtcamp.google_login_services` | Define any additional services. | <ul><li>`container` - Container object.</li></ul>
| `rtcamp.google_user_authenticated` | Fires once the user has been authenticated via Google OAuth. | <ul><li>`user` - User object.</li></ul>
| `rtcamp.id_token_verified` | Do something when token has been verified successfully.<br />If we are here that means ID token has been verified.
| `rtcamp.google_user_logged_in` | Fires once the user has been authenticated. | <ul><li>`user_wp` - WP User data object.</li><li>`user` - User data object returned by Google.</li></ul>
| `rtcamp.google_user_created` | Fires once the user has been registered successfully. | <ul><li>`uid` - User ID</li><li>`user` - WP user object.</li></ul>
| `rtcamp.login_with_google_exception` | Fires when an exception is raised during token verification. | <ul><li>`exception` - The exception which is being raised.</li></ul>

## Shortcode

You can add the google login button to any page/post using shortcode: `google_login`

**Example:**
```php
[google_login button_text="Google Login" force_display="yes" /]
```

**Supported attributes for shortcode**

| Parameter      | Description                                                   | Values | Default            |
| -------------- | --------------------------------------------------------------| -------| ------------------ |
| button_text    | Text to show for login button                                 | string | Login with google  |
| force_display  | Whether to display button when user is already logged in      | yes/no | no                 |
| redirect_to    | URL where user should be redirected post login                | URL    | `wp-admin`         |

## Contribute
- For contributing to this plugin, please refer to [CONTRIBUTING.md](docs/CONTRIBUTING.md) for more details.

## Unit testing

Unit tests can be run with simple command `composer tests:unit`. 
Please note that you'll need to do `composer install` (need to install dev dependencies) for running
unit tests.

You should have PHP CLI > 7.1 installed. If you have Xdebug enabled with php, code coverage report will be
generated at `/tmp/report/html`

## Minimum Requirements

WordPress >= 5.5.0

PHP >= 7.4

## License

This library is released under
["GPL 2.0 or later" License](LICENSE).

## BTW, We're Hiring!

<a href="https://rtcamp.com/"><img src="https://rtcamp.com/wp-content/uploads/sites/2/2019/04/github-banner@2x.png" alt="Join us at rtCamp, we specialize in providing high performance enterprise WordPress solutions"></a>
