# WP Google Login

Minimal plugin which allows WP user to login with google.

#### How to use it?

1. Create project from [Google console](https://console.developers.google.com/apis/dashboard) if not exists.
2. Go to **Credentials** tab, And create credential for OAuth client.
    * Application type will be **Web application**
    * Add `YOUR_DOMAIN/wp-login.php` in **Authorized redirect URIs**
3. This will give you Client ID and Secret key.
4. Use below snippet, replace value of const with client id and secret. And add that in to `wp-config.php` 

```php
define( 'WP_GOOGLE_LOGIN_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID' );
define( 'WP_GOOGLE_LOGIN_SECRET', 'YOUR_SECRET_KEY' );
```

#### How to disable registration?
1. By default, user registration is disabled, which means if the user does not exist with that email address then plugin won't register a new user. To enable that you can simply define const `WP_GOOGLE_LOGIN_USER_REGISTRATION` and set value `true`. See below example:

**Note:** If this const is defined then, It will override WordPress's option ( "Anyone can register" ). Otherwise it will follow settings from WordPress's option.

```php
define( 'WP_GOOGLE_LOGIN_USER_REGISTRATION', true );
```

#### How to restrict login with a specific domain?

1. Add your domain name (without any schema and `www.`) in `WP_GOOGLE_LOGIN_WHITELIST_DOMAINS` const. You can whitelist multiple domain with comma separated value. See below example:

**Note:** If a user already exists then it will allow a user to login with Google regardless of its domain is whitelisted or not. It will only prevent the user from registering with an email address with a domain that not whitelisted.
 
```php
define( 'WP_GOOGLE_LOGIN_WHITELIST_DOMAINS', 'example.com, sample.com' );
```

## wp-config.php parameters list

| Const                             | Type   | Description                                                                                                                                                                                                                                                                                                                                                |
|-----------------------------------|--------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| WP_GOOGLE_LOGIN_CLIENT_ID         | String | Google client ID of your application.                                                                                                                                                                                                                                                                                                                      |
| WP_GOOGLE_LOGIN_SECRET            | String | Secret key of your application                                                                                                                                                                                                                                                                                                                             |
| WP_GOOGLE_LOGIN_WHITELIST_DOMAINS | String | (Optional) Domain name if you want to restrict login with your custom domain. By default, It will allow all domains. An e.g. If the value set to "example.com" it will only allow email addresses with "example.com" domain. You can add multiple with comma separated. Note that if a user already exist in WordPress then it will allow a user to login. |
| WP_GOOGLE_LOGIN_USER_REGISTRATION | true   | (Optional) Set True If you want to enable new user registration if the user does not exist. By default, It won't register new user if not exists. Note that If this const is defined then, It will override WordPress's option ( "Anyone can register" ).                                                                                                  |
| WP_GOOGLE_LOGIN_USE_MAIN_SITE_URL | true   | (Optional) Used for MU sites, It will use top level domain for authentication. So, you don't have to add every site's URL into Google console as authorized URL.,Note : Do not use, if your main site use SSL and sub site isn't.                                                                                                                          |

