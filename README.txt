=== Generate Security.txt ===
Contributors: verenigingvanregistrars
Tags: security, security.txt, responsible disclosure
Requires at least: 6.3
Tested up to: 6.7
Stable tag: 1.0.8
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

With a security.txt file, ethical hackers can easily send you a notification when they have found a vulnerability on your website.

== Description ==

Security.txt is an open standard (RFC 9116) that allows ethical hackers and security researchers to contact you when they have found a vulnerability on your website.

The principle is simple and effective: contact information is put into a txt file and placed in a fixed location in your website's directory structure (well-known folder). In this way, contact can easily be made.

This plugin helps you to create and place the security.txt file without any knowledge of the open standard. This makes you easily accessible in case something is wrong with your website.

== Installation ==

1. Upload `generate-security-txt` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Go the Tools > Generate Security.txt
4. Find out of you have any critical requirements that you miss through the plugin admin interface like HTTPS or the PHP-extension 'gnupg'.
5. Generate your keys and security.txt

== Frequently Asked Questions ==

= What if I don't have the PHP-extension 'gnupg' =

You will not be able to generate keys and sign your security.txt. This isn't a full requirement as per securitytxt.org, but an internet.nl validation will not green-light the file.

We recommend contacting your webhostingprovider and ask them how to enable this extension.

= What if I don't have HTTPS =

Your security.txt file will not be valid without URIs starting with 'https://'. It's critical as per securitytxt.org standards

== Screenshots ==

1. /assets/screenshot-1.png
2. /assets/screenshot-2.png

== Changelog ==

= 1.0.8 =
Updated internet.nl conform
New log feature
New file has verification feature

= 1.0.6 =
Normalize end of line characters

= 1.0 =
* Initial release version

== Upgrade Notice ==

= 1.0.8 =
Updated internet.nl conform
New log feature
New file has verification feature

= 1.0.6 =
Normalize end of line characters

= 1.0 =
Update to phpseclib latest version