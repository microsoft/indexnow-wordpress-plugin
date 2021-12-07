=== Microsoft IndexNow Plugin ===
Contributors: bingwebmastertools
Plugin link: https://bing.com/indexnow
Tags: seo, crawling
Requires at least: 5.3
Tested up to: 5.8
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.txt
Requires PHP: 5.6.20

Microsoft IndexNow Plugin for WordPress enables site owners to instantly and automatically submit their new/updated
pages to supporting search engines.


== Description ==

IndexNow Plugin for WordPress enables automated submission of URLs from WordPress sites to the multiple search engines without the need to register and verify your site with them. Once installed, the plugin will automatically generate and host the API key on your site. It detects page creation/update/ deletion in WordPress and automatically submits the URLs in the background. This ensures that search engines will always have the latest updates about your site.

Some other handy features included in the plugin:

* Toggle the automatic submission feature.
* Manually submit a URL to IndexNow.
* View list of recent URL submissions from the plugin.
* Retry any failed submissions from the recent submissions list.
* Download recent URL submissions for analysis.
* Stats on recent successful and failed submissions

You can browse the code at the [GitHub repository](https://github.com/microsoft/indexnow-wordpress-plugin).

This plugin was developed with love and coffee by the Microsoft Bing team.


== Frequently Asked Questions ==

= How can I change the API key? =

To generate a new API key, please de-activate and re-activate the plugin. It will automatically generate a new key and host on your site. API key is unique for a website and hence should not be changed too frequently as best practices.

= How can I delete any stored data in my WordPress database? =

You can go to ‘Plugins’ page from your WordPress sidebar and click on Deactivate under Microsoft IndexNow plugin. This will remove the API key integration as well as any locally stored data about submitted URLs. Reactivating the plugin will present you with a clean slate. 

= I cannot see all the URLs submitted on my dashboard? Where can I view all my submissions made via Microsoft IndexNow WordPress plugin? 

We are providing a limited number of URLs submitted on the WordPress dashboard (20 successful and 20 failed). Please reach out to individual search engines for more details. However, if you wish to see the URLs submitted, specifically for Microsoft Bing, please login and verify your site on Bing webmaster tools and you can view more submissions via [URL submission Tool](https://www.bing.com/webmasters/submiturl).

= I can’t see the URLs in search engines indexed? =

Indexing of URLs is specific and dependent on each search engine’s rules, please reach out to individual search engine for debugging and resolution.


== Changelog ==

= 1.0.0 =
* Initial release.
