=== Flyn Login Lockdown ===
Tags: security, login
Requires at least: 3.2.0
Tested up to: 5.1.1
Stable tag: 1.1

Flyn Login Lockdown prevents brute force login attacks/attempts on your WordPress installation.

== Description ==

Flyn login lock down is a way to protect your WordPress blog from brute force login attacks.

How it works:
1. An attacker attempts to login and fails
2. Simple Login Lockdown record that failed login. Both IPs and usernames have failure limits (defaults to 5)
3. After a certain number of failed attemps, further attemps to access the wp-login.php page are blocked for a time in the case of IP lockouts, and further attempts to log in with a specific username are refused in the case of username lockouts (defaults to 1 hour)
4. A notification is send to system administrators of the lockout

If you happen to forget your password and make a failed login attempt yourself, the plugin will clear out the lockdown count data on successful login. Alternatively, the lockout email sent to administrators has an unlock link (can be disabled) if this was a mistake.

== Installation ==

1. Extract `flyn-login-lockdown` folder to your wp-content/plugins directory
2. Login into your website and activate the plugin

== Frequently Asked Questions ==

= I got locked out, what do I do? =

Simple answer: wait.  The lockdown will clear in the time you specified, just visit the site again later. Alternatively if this feature is enabled (disabled by default) and you're a system administrator, an unlock code is included in the email.

If you absolutely need to get into your site right now, you can can do one of two things...
1. FTP into your site and rename the `flyn-login-lockdown` plugin folder to anything else
2. Access your sites database and search for `locked_down_` in the `option_name` column of the `wp_options` table.  Delete the records you find -- they should be "transients".

== Changelog ==

= 1.0 =
* First version