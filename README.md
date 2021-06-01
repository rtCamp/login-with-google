# Login with Google

> WordPress plugin to login/register with google

1. [Overview](#overview)
2. [Installation](#installation)
3. [Usage Instructions](#usage-instructions)
4. [Shortcode](#shortcode)
5. [Minimum Requirements](#minimum-requirements)
6. [License](#license)

## Overview

Login with google provides seamless experience for users to login in to WordPress 
sites using their google account. No need to manually create accounts, no need to remember quirky
passwords. Just one click and land into the site!

## Installation

1. Clone this repository.
2. Run `composer install --no-dev` from inside the cloned directory.
3. Upload the directory to `wp-content/plugins` directory.
4. Activate the plugin from WordPress dashboard.

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
website, you enter `myorg.com` in whitelisted domains. Users with github primary 
email like `abc@myorg.com` will be able to register on website. Contrary to this, users with emails like
`something@gmail.com` would not be able to register here.
   
## Shortcode

You can add the github login button to any page/post using shortcode: `gh_login` 

**Example:**
```php 
[google_login button_text="Google Login" force_display="yes" /]
```

**Supported attributes for shortcode**

| Parameter      | Description                                                   | Values | Default            |
| -------------- | --------------------------------------------------------------| -------| ------------------ |
| button_text    | Text to show for login button                                 | string | Login with github  |
| force_display  | Whether to display button when user is already logged in      | yes/no | no                 |
| redirect_to    | URL where user should be redirected post login                | URL    | `wp-admin`         |


## Minimum Requirements

WordPress >= 5.4.0

PHP >= 7.1 

## License

This library is released under
["GPL 2.0 or later" License](LICENSE).
