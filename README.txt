=== Region Redirect ===
Contributors: James Schweda
Tags: cloudflare, redirect, geolocation, state-based redirect, country redirect
Requires at least: 5.0
Tested up to: 6.5
Stable tag: 2.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Redirect users based on their U.S. state (via Cloudflare `CF-Region-Code`) or country (via `CF-IPCountry`). Highly customizable via a settings page.

== Description ==
Region Redirect allows site administrators to redirect visitors from selected U.S. states or foreign countries using Cloudflare headers. Admins can enable redirection per state or country and customize the destination URLs.

== Installation ==
1. Upload the plugin folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to Settings → Region Redirect to configure redirection.

== Frequently Asked Questions ==
= Where do I configure redirection rules? =
Navigate to *Settings → Region Redirect*.

= Which Cloudflare headers are used? =
- `CF-Region-Code` for U.S. states
- `CF-IPCountry` for countries

== Changelog ==
= 2.0 =
* Major update: UI settings for all 50 U.S. states and 40+ countries.
* Default redirects: Texas, Kansas, Indiana enabled.
* Default country redirect: https://eff.org

= 1.2 =
* Initial version: Redirects based on Texas, Kansas, and Indiana by default.
