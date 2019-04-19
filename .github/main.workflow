workflow "Run Code Review" {
  resolves = ["PHPCS Code Review"]
  on = "pull_request"
}

action "PHPCS Code Review" {
  uses = "rtCamp/action-phpcs-code-review@master"
  secrets = ["GH_BOT_TOKEN"]
}
