=== Plugin Name ===
Contributors: wordplus
Donate link: https://www.wordplus.org/donate/
Tags: BuddyPress, messages, bp messages, private messages, pm, chat, live, realtime, chat system, communication, messaging, social, users, ajax, websocket 
Requires at least: 4.0
Tested up to: 4.7.1
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

**BP Better Messages** – is a fully featured replacement for standard BuddyPress Messages.
Plugin is fully backward compatible with BuddyPress Messages.

**[More Info & Demo](https://www.wordplus.org/downloads/bp-better-messages/)**

**Improved features comparing to standard system:**

* AJAX or WebSocket powered realtime conversations
* Reworked email notifications ([More info](https://wordpress.org/plugins/bp-better-messages/faq/))
* Fully new concept and design
* Files Uploading
* Embedded links with thumbnail, title, etc...
* Emoji selector (using cloudflared CDN to serve EmojiOne)
* Message sound notification
* Whole site messages notifications (User will be notified anywhere with small notification window)

**Supported features from standard messages system:**

* Private Conversations
* Multiple Users Conversations
* Subjects
* Mark messages as favorite

**WebSocket version:**

WebSocket version is a paid option, you can get license key on our website.

We are using our server to implement websockets communications between your site and users.

Our websockets servers are completely private and do not store or track any private data.

* **Significantly** reduces the load on your server 
* **Instant** conversations and notifications
* Typing indicator (indicates if another participant writing message at the moment)
* Designed to work with shared hosting
* More features coming!

[Why WebSockets are a game-changer?](https://pusher.com/websockets)

**[Get WebSocket version license key](https://www.wordplus.org/downloads/bp-better-messages/) | [Terms of Use](https://www.wordplus.org/end-user-license-agreement/)**

Languages:

* English

**This is a new plugin, so please use support forums if you have found any bug or have any other question please use forums to contact us! :)**

== Frequently Asked Questions ==

= How email notifications works? =

Instead of standard notification on each new message, plugin will group messages by thread and send it every 15 minutess with cron job. 

* User will not receive notifications, if they are disabled in user settings. 
* User will not receive already read messages. 
* User will not receive notifications, if he was online last 10 minutes or he has tab with opened site

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/bp-better-messages` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Use the Settings -> BP Better Messages to configure the plugin

== Screenshots ==

1. Thread screen
1. Embedded links
1. Thread list screen
1. New Thread screen
1. Writing notification
1. Onsite notification
1. Files attachments

== Changelog ==

= 1.7.2 =
* Security improvement

= 1.7 =
* Possible to create new lines with Shift + Enter
* Paste files fixed multiple files sending
* Private browser bug
* Line breaks not removing in new thread anymore

= 1.6.5 =
* BP Notification will not added on each message anymore
* Improved files design

= 1.6.4 =
* Multiple bugfixes and improvements
* Improved emojies

= 1.6.3 =
* Fixed files uploading for default users.
* Another bugfixes

= 1.6.2 =
* Fixed fatal error, when BP Messages component wasnt active

= 1.6.1 =
* Nice attached files and images styling
* Attached video embed
* Attached audio embed
* Multiple bugfixes and improvements

= 1.6 =
* File Uploading initial
* Multiple bugfixes and improvements

= 1.5.1 =
* Online indication (websocket version)
* Multiple bugfixes and improvements

= 1.5 =
* Replaced Standard Email notifications with grouped messages
* Multiple bugfixes and improvements

= 1.4.4 =
* WebSocket Method polished and should work perfect now
* Multiple bugfixes and improvements
* CSS improvements

= 1.4.3 =
* AJAX Method polished and should work perfect now
* CSS polished

= 1.4.2 =
* Embedded links 404 fix
* No more double notifications if 2 threads opened in different tabs
* Added AJAX Loader

= 1.4.1 =
* Embedded links improvements

= 1.4 =
* Multiple bugfixes and improvements
* Embedded links feature!

= 1.3.2 =
* Prefix fix

= 1.3.1 =
* Remove BBPress functions

= 1.3 =
* Multiple bugfixes 
* Messages menu in topbar replaced

= 1.2 =
* Added starred messages screen
* Added thread delete/restore buttons
* Added empty screens

= 1.1 =
* Code refactoring and minor improvements

= 1.0 =
* Initial release