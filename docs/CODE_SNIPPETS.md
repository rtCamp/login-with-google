# Code Snippets
> Code snippets to customize and extend the pluign.

1. **Assign different user role to user created by the plugin**
```php
add_action( 'rtcamp.google_user_created', function( $user_id ) {
   // Get WP_User object from user_id.
   $user = get_user( $user_id );

   // Get the default role set under Settings -> General.
   $default_role = get_option( 'default_role' );

   // Remove default assigned role.
   $user->remove_role( $default_role );

   // Assign different role.
   $user->add_role( 'editor' );
} );
```
