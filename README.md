# Bing URL Submissions Plugin

Bing URL Submission Plugin for WordPress enables WordPress site owners to instantly and automatically submit their new and updated pages to the Bing index. Once setup, the plugin detects any page creation or update in WordPress and automatically submits the URL behind the scenes ensuring that the site content is always fresh in Bing index.

Some other handy features included in the plugin:

- Toggle automatic submission feature on or off.
- Manually submit a site URL to Bing Index.
- View list of recent URLs submitted by the plugin.
- Retry any failed submissions from recent submission history.
- Download recent submissions list.

This plugin was developed with love and coffee by the Bing Webmaster team.

## Getting Started

Follow instructions below to install the plugin and set up automatic submission of new pages in your WordPress site to the Bing index.

### VERIFYING YOUR WORDPRESS SITE ON BING WEBMASTER

1. Log in to [Bing Webmaster][bing-webmaster] (or sign up for an account).
1. Follow instructions [here][verify-wordpress] to verify your WordPress site with Bing Webmaster.
1. Once verified, you can access your API key by following the instructions [here][api-key-access].

### INSTALLING THE PLUGIN

1. Log in to the admin panel for your WordPress site. Click on `Plugins` > `Add New`.
1. Search for "Bing URL Submissions Plugin" and install.
1. Once installed, click on `Activate` to enable plugin.

### SETTING UP THE PLUGIN

1. Open Bing URL Submissions plugin settings page by clicking on `Settings` link for the plugin. (Or the `Bing Webmaster` link in the navigation menu).
1. Enter your Bing Webmaster API key into the API key prompt in the plugin page.

Voila! Your WordPress site is now configured to automatically submit URLs to Bing.

## Frequently Asked Questions

- Why should I install Bing URL Submission Plugin?

Bing Webmaster enables quick indexing of your site URLs via the Submit URL API. Bing URL Submissions Plugin automates the submissions of your site URLs to this API by automatically submitting URLs for any page updated/created from WordPress.

- Where do I find the API key?

To automate Bing URL submissions using the Bing URL Submission Plugin, you need to have your WordPress site registered with Bing Webmaster. Once your site is verified at Bing Webmaster, you can access your API key by navigating to `Settings` > `API Access` > `API Key` within [Bing Webmaster portal][bing-webmaster].

- I got an error "Adding API key failed: Invalid API Key" when I'm trying to log in to the plugin dashboard using API key. What do I do?

"Invalid URL" error indicates that your API key is invalid for the WordPress site you're trying to configure the plugin against. Please verify that your WordPress site is added and verified against your Webmaster account.

- "Automatic URL Submission" and "Manual URL Submission" cards are greyed out in plugin dashboard. How do I fix this?

Automatic and manual URL submission cards are disabled if your API key is detected as invalid for submitting URLs against this WordPress site. Please ensure that your site is verified in your Bing Webmaster account and update the plugin with your new API key using 'Update key' option in the API key card.

- I got an error "Invalid API key : Update API key to enable Automatic & Manual URL submission.". What do I do?

See answer to `"Automatic URL Submission" and "Manual URL Submission" cards are greyed out in plugin dashboard. How do I fix this?` above.

- How can I reset the plugin and delete any stored data from my WordPress database?

You can go to 'Plugins' page from your WordPress side menu and click on `Deactivate` under Bing URL Submissions plugin listing. This will remove the API key integration as well as any locally stored data about submitted URLs. Reactivating the plugin will present you with a clean slate and ask for API key input.

## Changelog

### 1.0.12
- Fix: Compatibility issue with older wordpress versions.

### 1.0.11

- Fix: Non public URL subimssions.
- Fix: Upgrade dependencies to fix vulnerabilities.

### 1.0.10

- Upgrade dependencies to fix vulnerabilities.

### 1.0.9

- Update readme to reflect support for WordPress v5.7.

### 1.0.8

- Upgrade dependencies to fix vulnerabilities and update latest compatible wordpress version.

### 1.0.7

- Fix console error being thrown by React when not in plugin page.

### 1.0.6

- Upgrade dependencies to fix known vulnerabilities.

### 1.0.5

- Filter out URLs containing specific post_types that aren't browsable.

### 1.0.4

- Fix encoded URLs being displayed in URL Submissions table.

### 1.0.3

- Update root element name. Fixes issue loading plugin settings page.

### 1.0.2

- Rename root element of settings page to avoid conflicts with other plugins.

### 1.0.1

- Update readme to reflect support for WordPress v5.5.

### 1.0.0

- Initial release.

[bing-webmaster]: https://bing.com/webmasters
[verify-wordpress]: https://docs.microsoft.com/bingwebmaster/verifying-wordpress
[api-key-access]: https://docs.microsoft.com/bingwebmaster/getting-access#using-api-key
