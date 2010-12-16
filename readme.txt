=== Facebook Connect ===
Contributors: mufasa, valentinas
Tags: facebook, facebook connect, fb connect, fbconnect, fb-connect, login.
Requires at least: 3.0
Tested up to: 3.01
Stable tag: 1.0

A beautifully crafted light weight Facebook Connect Plugin that uses the new Facebook API to create WordPress user accounts.

== Description ==

A beautifully crafted light weight Facebook Connect Plugin that uses the new Facebook API to create WordPress user accounts. 

When a user clicks the Facebook Connect button the Plugin checks to see if the user already has a WP profile in the website that corresponds with their Facebook email address. 

If so Facebook Connect logs the user in. If the user doesn't have an existing WordPress account then the Facebook Connect Plugin will create them new one and log the user in. 

Features include:
1) Shortcode - place the Shortcode anywhere on your site and the FB-connect button will appear.

2) Widget - place the widget in your sidebar and the FB-connect button will appear.


Inspired by these Plugins that use the old depreciated Facebook API:
http://wordpress.org/extend/plugins/wp-facebookconnect/
http://wordpress.org/extend/plugins/bp-fbconnect/

== Installation ==


1. Upload files to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Go to Settings->FB connect and set your facebook application API key and application secret
1. Add a widget, or place a shortcode and start connecting!

The shortcode is `[fb_login]`. You can also specify custom size (available options: small, medium, large, xlarge), login text and logout text. Example:
`[fb_login size='xlarge' login_text='Logout' logout_text='Logout']`
You can place this anywhere in post or page. You can also place the shortcode in your template, however it's a bit different, example:
`<?php do_shortcode("[fb_login size='xlarge' login_text='Logout' logout_text='Logout']"); ?>`

== Changelog ==

= 1.0 =
* Big bang. Time and space starts here.