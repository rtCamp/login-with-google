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

#### How to restrict login with a specific domain?

1. Add your domain name (without any schema and `www.`) in `WP_GOOGLE_LOGIN_HOSTED_DOMAIN` const. See below example:

```php
define( 'WP_GOOGLE_LOGIN_HOSTED_DOMAIN', 'example.com' );
```

#### How to disable registration?
1. By default, user registration is enabled, which means if the user does not exist with that email address plugin will create it and process authorization. To disable that you can simply define const `WP_GOOGLE_LOGIN_DISABLE_REGISTRATION` and set value `true`. See below example:
```php
define( 'WP_GOOGLE_LOGIN_DISABLE_REGISTRATION', true );
```

## wp-config.php parameters list

| Const                                | Type   | Description                                                                                                                                                                                                                        |
|--------------------------------------|--------|------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| WP_GOOGLE_LOGIN_CLIENT_ID            | String | Google client ID of your application.                                                                                                                                                                                              |
| WP_GOOGLE_LOGIN_SECRET               | String | Secret key of your application                                                                                                                                                                                                     |
| WP_GOOGLE_LOGIN_HOSTED_DOMAIN        | String | (Optional) Domain name if you want to restrict login with your custom domain. By default, It will allow all domains. e.g.if value set to "example.com" it will only allow email addresses with "example.com" domain.               |
| WP_GOOGLE_LOGIN_DISABLE_REGISTRATION | true   | (Optional) Set True If you want to disable new user registration if user is not exists. By default, It will register user if not exists.                                                                                           |
| WP_GOOGLE_LOGIN_USE_MAIN_SITE_URL    | true   | (Optional) Used for MU sites, It will use top level domain for authentication. So, you don't have to add every site's URL into Google console as authorized URL.  Note : Do not use, if your main site use SSL and sub site isn't. |
