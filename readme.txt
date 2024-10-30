=== Captcha Spam Blocker ===
Contributors: botezatu
Tags: captcha, security, spam protection, antispam
Requires at least: 4.0
Tested up to: 6.6.2
Stable tag: 2.0.0
Requires PHP: 5.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Enhance your site’s security with dynamic CAPTCHA, blocking spam and bot access on forms. GDPR-compliant.

== Description ==

Elevate the security of your WordPress and WooCommerce sites with our comprehensive CAPTCHA and Security Enhancements Plugin. Designed to shield your site from unauthorized access and spam, this plugin integrates CAPTCHA protection across various critical points including the WordPress admin login, registration, and lost password forms, as well as on comments and WooCommerce-related forms such as login, registration, lost password, ratings, reviews, and checkout processes.

Our Captcha Spam Blocker Plugin is fully GDPR-compliant, ensuring that no personal data is unnecessarily collected or stored.

== Key Features ==

- CAPTCHA Integration: Adds CAPTCHA verification to prevent automated submissions on WordPress and WooCommerce forms, including:
  - WP-admin login, register, and lost password pages.
  - Comments section.
  - WooCommerce login, register, lost password, ratings and reviews, and checkout forms.

- Enhanced CAPTCHA Loading: CAPTCHA images are dynamically loaded using AJAX with a security token and encoded in Base64, ensuring both security and performance are optimized.

- Contact Form 7 Compatibility: Secure your CF7 forms against spam with our easy-to-integrate CAPTCHA shortcode `[csb_botezatu_captcha_spam_blocker]`.

- Advanced Security Options:
  - Honey Pot CAPTCHA: Implement an invisible form field that traps bots without affecting user experience.
  - JavaScript CAPTCHA Layer: Enhances security with a dynamically updated input field to challenge bots further.

- Disable XMLRPC: Protect your site from brute force attacks through the XMLRPC endpoint, commonly exploited for unauthorized login attempts.

- Stop Words Filter: Control unwanted content in comments and messages with a customizable list of stop words, preventing the submission of forms containing these terms.

== Benefits ==

- Enhanced Security: Reduce the risk of spam and unauthorized access.
- User-Friendly: CAPTCHA challenges are designed to be unobtrusive and user-friendly.
- Easy Integration: Simple setup with minimal configuration needed to protect various forms across your WordPress and WooCommerce sites.

== Usage ==

Easily integrate CAPTCHA in your Contact Form 7 with the provided shortcode and follow our guidelines to enhance security across your site's forms. For added protection, enable the recommended settings like Honey Pot CAPTCHA and JavaScript security layer.

Secure your WordPress site today with our Captcha Spam Blocker Plugin — your all-in-one solution for a safer, spam-free website.

== Installation ==
1. Upload 'captcha-spam-blocker.zip' to the '/wp-content/plugins/' directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.

== Screenshots ==
1. Example of CAPTCHA on the WordPress login page.
2. Example of CAPTCHA integration on the WordPress comment form.
3. CAPTCHA Admin Screen #1
4. CAPTCHA Admin Screen #2
5. CAPTCHA Admin Screen #3

== Changelog ==
= 2.0.0 =
* Code optimization for improved performance and efficiency

= 1.0.0 =
* Initial release
