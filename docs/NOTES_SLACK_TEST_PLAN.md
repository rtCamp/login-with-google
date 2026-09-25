# Notes → Slack review and test plan

This plan covers the optional Slack integration in the Login with Google plugin.
Review the screens on a staging WordPress site before enabling it on a production
site. The feature branch is `feature/notes-slack-notifications` and targets
WordPress 7.1 for the complete Notes experience.

## Review setup

1. Install the preview ZIP through **Plugins → Add New → Upload Plugin** on a
   staging site. If Login with Google is already installed, use WordPress's
   replace-current upload flow after taking a backup. Do not replace the
   production plugin for a UI review. The settings screen is at
   `/wp-admin/options-general.php?page=login-with-google&tab=notes-slack`.
2. Use WordPress 7.1 or newer, PHP 7.4 or newer, and a working WP-Cron runner.
   Keep the existing Google sign-in configuration in place for regression
   checks.
3. In a test Slack workspace, install a bot with `chat:write`, `im:write`,
   `users:read`, and `users:read.email` scopes. Do not put the bot token in a
   screenshot or bug report. This is an outbound integration; no Slack event
   subscription or public callback URL is needed.
4. Prepare these WordPress accounts: an administrator who also belongs to the
   Slack workspace, a post author, a second editor, a user whose WordPress and
   Slack emails differ, and a user with no Slack account. Prepare a draft post
   authored by the post author.
5. Use the Slack desktop or mobile app to test the profile's **Incorrect?**
   link. Slack documents `slack://user?team=…&id=…` as a native-client DM link.

## Screens to review

| Screen | Where to find it | What to inspect |
| --- | --- | --- |
| Disconnected setup | Settings → Login with Google → Notes → Slack → Notifications | Workspace status, token field, recipient choices, admin contact choice, save action. |
| Connected setup | Same screen after connecting | Workspace name, enabled state, test DM and disconnect actions, delivery error state. |
| People mapping | Settings → Login with Google → Notes → Slack → People mapping | WordPress email, automatic Slack ID, manual override, effective ID, page navigation. |
| Member profile | Users → Profile as a non-admin | Workspace, effective Slack ID, match source, **Incorrect?** DM link. |
| Admin editing a member | Users → All Users → Edit a user | Same identity details plus admin-only override field. |
| Slack notification | Slack DM from the installed bot | Note author, post title, Note body, **Open Note** link. |
| Note destination | Follow **Open Note** while signed in | Post editor opens the linked Note thread. |

Capture screenshots of the first five screens and one sample DM. Redact bot
tokens and any private Note text that should not be shared with reviewers.

## Manual test cases

Record each case as Pass, Fail, or Blocked, with the WordPress version, PHP
version, browser, user role, and a screenshot or short recording for failures.

| ID | Priority | Steps | Expected result |
| --- | --- | --- | --- |
| S01 | P0 | Open Notifications before connecting Slack. | Workspace shows **Not connected**; Google sign-in settings remain available. |
| S02 | P0 | Save an invalid bot token, then a valid one. | Invalid token is rejected without enabling DMs. Valid token shows the correct workspace. |
| S03 | P0 | Choose notification recipients and a WordPress admin contact; enable and save. Reload. | All choices persist. The contact list contains WordPress administrators only. |
| S04 | P0 | Send the current admin a test DM. | One DM arrives. If Slack email differs, add a manual override and retry. |
| M01 | P0 | Open People mapping, click **Check email matches on this page**, then visit page 2. | Each row shows the WordPress email and Slack match state; pagination preserves the People screen. |
| M02 | P0 | Enter a valid Slack ID override for an email alias and save. | Effective ID changes to the override; the user's profile says **Administrator override**. |
| M03 | P0 | Clear the override and save. | Effective ID returns to the email match, or **No match** if none exists. |
| M04 | P0 | Submit an invalid override such as `not-an-id` alongside a valid change. | No override on that page changes; an error notice explains the accepted ID format. |
| M05 | P0 | Connect a different test Slack workspace after saving an override. | The old workspace's override is not used for notifications or profile display. |
| P01 | P0 | View a matched non-admin user's own Profile. | Workspace name and ID, effective Slack ID, and **Matched by WordPress email** appear. No override input appears. |
| P02 | P0 | View a user with an override, a user without a Slack match, and a profile while Slack is disconnected. | Each state is explained accurately; the disconnected state does not claim an email mismatch. |
| P03 | P0 | Click **Incorrect? Contact your website admin in Slack** from a non-admin profile. | Slack opens a DM with the configured WordPress administrator in the connected workspace. No message is sent automatically. |
| P04 | P0 | Select a contact administrator with no Slack account, and ensure no other WordPress administrator has a Slack match. Reload a member profile. | The link is replaced with an explanation; it does not point to an unknown user. |
| N01 | P0 | Have an editor add a Note to a post authored by another user. | The post author receives one DM; the Note author does not receive their own DM. The message includes author, title, body, and link. |
| N02 | P0 | Mention a third WordPress user in a new Note on WordPress 7.1+. | The mentioned user receives one DM if they can edit the Note; a person without access receives none. |
| N03 | P0 | Reply to a Note as another editor. | The original Note author is notified once; duplicate recipient roles do not produce duplicate DMs. |
| N04 | P0 | Follow **Open Note** as an eligible recipient, then as a user without access. | Eligible user lands on the correct thread; an ineligible user cannot view the private Note through the link. |
| N05 | P0 | Edit an existing Note, disable notifications, then add a new Note. | Editing does not resend; after disabling, new Notes do not send DMs. |
| N06 | P1 | Temporarily interrupt Slack or WP-Cron, then restore it. | Transient delivery failures are retried without duplicating successful recipients; the settings screen shows a sanitized error code. |
| R01 | P0 | Log out and sign back in with the existing Google login flow. | Sign-in, registration settings, and existing account mapping still work. |
| A01 | P1 | Navigate each new screen using only a keyboard and a screen reader. | Tabs, fields, row labels, notices, and the contact link have understandable names and a usable focus order. |

## Automated gates and release method

Run these from the repository root with the required PHP, Composer, Node, and
npm versions installed:

```sh
composer install
composer cs
composer tests:unit
npm ci
npm run lint:js:notes
npm run lint:js
npm run lint:style
DISABLE_NOTIFICATIONS=1 npm run production
```

`composer cs` uses the repository's `phpcs.xml`: WordPress Core, Docs, and
Extra standards, PHPCompatibilityWP, WordPress VIP rules, translation text
domain checks, and variable analysis. `composer cs` passes on this branch. The
PR's rtCamp PHPCS workflow runs on pull requests. The repository's PHPUnit
workflow currently runs only by manual dispatch, so run `composer tests:unit`
locally and compare failures with the unchanged `develop` branch. The full
suite currently has 14 errors and 2 failures on both branches; these are
existing failures, not a pass. Focused Notes Slack unit tests pass separately.

Use Node 22 for the asset build. On macOS, disable Laravel Mix's desktop
notifications as shown above. Node 26 fails in the current Webpack dependency.
`npm run lint:js:notes` passes for the new editor-link script. The repository's
existing `npm run lint:js` fails on five issues in the login-button block
(missing declared WordPress packages and an unused argument). The existing
`npm run lint:style` command passes JS and JSON to Stylelint and fails on
syntax. These are tooling debt to fix before treating the full JavaScript and
style checks as release gates.

After static checks, build a release-format ZIP and inspect it for
`vendor/autoload.php`, compiled assets, and `assets/js/note-deeplink.js`.
Install that ZIP on staging. Run the P0 screen and integration cases with a
real test Slack workspace and WordPress users. Keep the PR in draft until the
live Slack cases and Google sign-in regression pass, and record any P1
limitations explicitly.

For each failure, attach the case ID, screenshot or screen recording, expected
and actual result, WordPress/PHP/browser versions, user role, and any sanitized
Slack error code shown on the Notifications screen. Confirm the fix by
repeating that case and the Google sign-in regression case.
