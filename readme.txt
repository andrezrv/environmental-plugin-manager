=== Environmental Plugin Manager ===
Contributors: andrezrv
Tags: plugin, plugins, testing, staging, production, development, local, environment
Requires at least: 3.0
Tested up to: 3.6.1
Stable tag: 1.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Gives you the option to define which plugins must be active for a particular environment only.

== Description ==

If you develop locally, in some point you'll have some plugins active in your local environment that you don't need anymore when you deploy your code and database to staging or production. Sure, you can deactivate them before or after deploying, but you're gonna need them again to be active if you want to perform changes to your code, or update your local database from your remote one. The whole process of activate and deactivate plugins for testing purposes could be really frustrating, and sometimes a complete waste of time.

Is in that kind of situations when the **Environmental Plugin Manager** can be a really helpful friend. Sadly, due to the sentitive work that this plugin performs, it doesn't work right out of the box, so besides from installing this plugin, you need to do some additional work.

**Please read the following instructions very carefully.**

## What do I need to make it work?

**The Environmental Plugin Manager works on some assumptions about your workflow:**

1. You have a constant named `WP_ENV_PLUGINS` defined in your WordPress configuration file (often `wp-config.php`).
2. The `WP_ENV_PLUGINS` value is one of the following: `development`, `staging`, `production`.
3. The value of `WP_ENV_PLUGINS` is different in each of your stages. Some developers prefer to keep different configuration files for each one of their environments, or change the values of their constants based on some evaluation. For example, you could have something like this in your `wp-config.php` file:

`if ( file_exists( dirname( __FILE__ ) . '/development-config.php' ) ) {
	define('WP_ENV_PLUGINS', 'development');
}
elseif ( file_exists( dirname( __FILE__ ) . '/staging-config.php' ) ) {
	define('WP_ENV_PLUGINS', 'staging');
}
else {
	define('WP_ENV_PLUGINS', 'production');
}`

**These assumptions are not going to change**, so you need to follow this practice in order to use this plugin correctly.

## How do I set up my environment?

Once you have installed this plugin (my recommendation is to do it first in development), you will notice that a new link appears under each active plugin of the list, which reads "Use for development only" (note that "development" could also be "staging" or "production", depending on your `WP_ENV_PLUGINS` constant). Keep that in mind and follow these steps:

1. Activate all the plugins that you need to use for your environment.
2. Click the "Use for development only" link for all the plugins you want to mark as development-only.

Once you click a link, that plugin will be added to the list of plugins that need to be active only in the current environment. You can click the "No more development only" link if you want to remove the plugin from the list.

## How do I reset my environment after a deploy?

Once you performed a complete deploy (files and database) to a different environment, let's say from development to staging, you will see that those plugins you selected in development to be active only in that environment are still active. Fear not! This is an intended behavior, as it would be insecure to change the status of the plugins without your knowledge, so you need to do it manually by just clicking the "Reset Plugins Environment (staging)" button that you see in your admin bar. After that, you should see your development-only plugins as not active.

That's pretty much it. You can test it yourself before deploying by just changing the values of `WP_ENV_PLUGINS`.

## Will this plugin work on MultiSite installations?

If you're using MultiSite, please note that you can activate and deactivate this plugin globally, but you cannot manage plugin environments for the whole network, just for individual sites. Also, this plugin cannot manage network activated plugins.

## Contribute

You can make suggestions and submit your own modifications to this plugin on [Github](https://github.com/andrezrv/environmental-plugin-manager).

== Installation ==

1. Unzip `environmental-plugin-manager.zip` and upload the `environmental-plugin-manager` folder to your `/wp-content/plugins/` directory.
2. Activate the plugin through the **"Plugins"** menu in WordPress.
3. Read carefully the instructions in [description page](http://wordpress.org/extend/plugins/environmental-plugin-manager/).

== Changelog ==

= 1.0 =
First public release.