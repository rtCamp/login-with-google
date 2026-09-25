# WordPress Notes notifications in Slack

This optional feature sends direct messages when a native WordPress Note or reply
is created. It uses the WordPress user's email address to find the same person
in Slack. It does not need a Google OAuth token or require the recipient to log
in after setup.

## Requirements

- WordPress 6.9 or newer for Notes. `@mention` recipients require WordPress 7.1.
- A Slack workspace where an administrator can install an app.
- WP-Cron (or a server cron that runs WP-Cron) for background delivery.

## Connect Slack

1. In Slack, [create an app from scratch](https://api.slack.com/apps) for the
   workspace. Give it a name such as “WordPress Notes”.
2. Under **OAuth & Permissions → Bot Token Scopes**, add `chat:write`,
   `im:write`, `users:read`, and `users:read.email`.
3. Select **Install to Workspace** and approve the permissions. Copy the
   **Bot User OAuth Token**, which begins with `xoxb-`.
4. In WordPress, go to **Settings → Login with Google → Notes → Slack**. Paste
   the token and save. You can set `WP_GOOGLE_LOGIN_SLACK_BOT_TOKEN` in
   `wp-config.php` instead; WordPress settings will not store the token in that
   case.
5. Select the recipient types, enable notifications, and save. Send a test DM
   to confirm the bot can reach your Slack account.

No incoming webhook, Slack event subscription, or channel is needed. The site
sends Notes directly to Slack's Web API. A bot token entered on the settings
page is stored in the WordPress options table; use the PHP constant when your
hosting environment supports secret management.

## Recipients

The defaults notify the post author and anyone mentioned in a new Note. On a
reply, the original Note author is also notified. The author of the new Note
never receives their own notification, duplicate recipients are removed, and
only users who can access the Note are eligible. Site developers can filter
recipient WordPress IDs with `rtcamp.google_notes_slack_recipient_ids`.

The plugin looks up a recipient by their WordPress email when their first
notification is sent, then caches the Slack user ID for that email and
workspace. An administrator may enter a Slack user ID override on the person's
WordPress profile if their emails differ. The People section displays users in
pages of 20 with their cached matching state; each page can be checked manually.

## Message links and delivery

Slack messages include the Note author's name, post title, Note body, and an
**Open Note** link. On WordPress 7.1, the plugin opens the post editor's Notes
sidebar and focuses the linked thread. The link is private and requires the
recipient to sign in with permission to edit the Note.

Only newly created Notes and replies are sent. Existing Notes are not
backfilled. Replies must be written in WordPress. WordPress's native Note emails
continue to follow **Settings → Discussion**. Delivery is queued using
WP-Cron and transient failures are retried twice; a sanitized last error code
appears on the Notes → Slack settings tab. The plugin never logs the bot token
or Note body.
