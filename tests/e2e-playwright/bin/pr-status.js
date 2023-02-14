#!/usr/bin/env node
// Octokit.js
// https://github.com/octokit/core.js#readme

const { Octokit } = require("@octokit/core");

const octokit = new Octokit({
  auth: process.env.TOKEN,
});

octokit.request("POST /repos/{org}/{repo}/statuses/{sha}", {
  org: "rtCamp",
  repo: "login-with-google",
  sha: process.env.SHA ? process.env.SHA : process.env.COMMIT_SHA,
  state: "success",
  conclusion: "success",
  target_url:
    "https://www.tesults.com/results/rsp/view/results/project/f121e5a9-27dd-4c39-85d3-1c138bbed9e8",
  description: "Successfully synced to Tesults",
  context: "E2E Test Result",
});