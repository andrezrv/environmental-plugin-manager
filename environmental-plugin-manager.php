<?php

/**
 * @package Env_Plugin_Manager
 * @version 1.0
 */

/*
Plugin Name: Environmental Plugin Manager
Plugin URI: http://wordpress.org/extend/plugins/environmental-plugin-manager/
Description: Gives you the option to define which plugins must be active for a particular environment only. You can activate and deactivate plugins separatedly for development, staging and production environments. To use this plugin, you need to add a constant named <code>WP_ENV_PLUGINS</code> to your <code>wp-config.php</code> file, with one of the following values: <code>development</code>, <code>staging</code>, <code>production</code>. If you're using MultiSite, please note that you can activate and deactivate this plugin globally, but you cannot manage plugin environments for the whole network, just for individual sites. Also, this plugin cannot manage network activated plugins.
Author: Andr&eacute;s Villarreal
Author URI: https://github.com/andrezrv/
Version: 1.1
*/


/**
 * Displays a warning message.
 */
function envpm_admin_warning() {

	if ( !envpm_environment_isset() ) {

		$html = '<div class="error"><p><strong>'. __( 'Warning', 'envpm' ) .':</strong> ' . __( 'Environmental Plugin Manager is active, but you have not defined the <code>WP_ENV_PLUGINS</code> constant. Please do it so and assign to it one of the following values in order for Environmental Plugin Manager to work: <code>development</code>, <code>staging</code>, <code>production</code>.', 'envpm' ) . '</p></div>';	    
	    echo $html;

	}

}


/**
 * Displays a success message.
 */
function envpm_admin_success() {
	
	$html = '<div class="updated"><p>' . sprintf( __( 'The list of active plugins was updated to the %s environment.', 'envpm' ), envpm_current_environment() ). '</p></div>';	    
	echo $html;

}


/**
 * Adds a button to the admin bar that allows to reset the environment for listed plugins.
 * 
 * @param object $wp_admin_bar The WordPress admin bar.
 */
function envpm_reset_button( $wp_admin_bar ) {

	if ( current_user_can( 'activate_plugins' ) and envpm_environment_is_valid() and !is_network_admin() ) {

		if ( envpm_has_auto_reset() ) {

			$args = array(
				'id' => 'env-plugins-reset-button',
				'title' => sprintf( __( 'Plugins Environment: %s. Auto-reset mode.', 'envpm' ), envpm_current_environment() ),
				'href' => '',
				'parent' => 'top-secondary',
				'meta' => array(
					'class' => 'env-plugins-auto-reset-mode'
				)
			);

		}
		else {

			$uri = $_SERVER['REQUEST_URI'];
			$separator = '';
			$key = '';

			if ( !stripos( $uri, 'reset_env_plugins' ) ) {
				$separator = stripos( $uri, '?' ) ? '&' : '?';
				$key = 'reset_env_plugins';
			}
			
			$args = array(
				'id' => 'env-plugins-reset-button',
				'title' => sprintf( __( 'Reset Plugins Environment (%s)', 'envpm' ), envpm_current_environment() ),
				'href' => $uri . $separator . $key,
				'parent' => 'top-secondary',
				'meta' => array(
					'class' => 'env-plugins-manual-mode'
				)
			);			
			
		}

		
		$wp_admin_bar->add_node($args);

	}

}


/**
 * Sets up configuration for current environment.
 * Shows a notice if you are in an admin screen. 
 */
function envpm_process_reset() {

	if ( ( isset( $_GET['reset_env_plugins'] ) or envpm_has_auto_reset() ) and envpm_environment_isset() ) {

		if ( envpm_reset() ) {
	
			if ( is_admin() and !envpm_has_auto_reset() ) {
				add_action( 'admin_notices', 'envpm_admin_success' );
			}
		
		}

	}

}


/**
 * Accepted values for WP_ENV_PLUGINS constant.
 */
function envpm_accepted_environment_values() {

	$accepted_values = array( 'development', 'staging', 'production' );
	return $accepted_values;

}


/**
 * Shortnames for each environment.
 */
function envpm_environment_shortnames() {

	$accepted_values = envpm_accepted_environment_values();
	$shortnames = array( $accepted_values[0] => 'dev', $accepted_values[1] => 'stage', $accepted_values[2] => 'prod' );

	return $shortnames;

}


/**
 * Obtains the shortname for a given environment.
 */
function envpm_get_environment_shortname( $environment ) {

	$shortnames = envpm_environment_shortnames();

	if ( isset( $shortnames[$environment] ) ) {
		return $shortnames[$environment];
	}

	return false;

}


/**
 * Returns the name of the current environment.
 */
function envpm_current_environment() {

	if ( envpm_environment_isset() ) {
		return WP_ENV_PLUGINS;
	}

}


/**
 * Checks if the environment is set.
 */
function envpm_environment_isset() {

	$isset = defined( 'WP_ENV_PLUGINS' );
	return $isset;

}


/**
 * Returns request key for current-environment-only option. 
 */
function envpm_key_for_only() {

	$key_for_only = envpm_get_environment_shortname( envpm_current_environment() ) . '_only';
	return $key_for_only;

}


/**
 * Returns request key for non-current-environment-only option. 
 */
function envpm_key_for_not_only() {

	$key_for_not_only = 'not' . envpm_key_for_only();
	return $key_for_only;

}


/**
 * Returns wp_option name for current-environment-only list of plugins.
 */
function envpm_option_key() {

	$option_key = envpm_option_key_with_environment( envpm_current_environment() );
	return $option_key;

}


/**
 * Returns wp_option name for given-environment-only list of plugins.
 */
function envpm_option_key_with_environment( $environment ) {

	$option_key = 'envpm_' . envpm_get_environment_shortname( $environment );
	return $option_key;

}


/**
 * Checks if the current environment is a valid one.
 */
function envpm_environment_is_valid() {

	$valid = ( in_array( envpm_current_environment(), envpm_accepted_environment_values() ) );
	return $valid;

}


/**
 * Processes the request for a plugin in order to add it or remove it 
 * from the list of plugins for the current environment.
 */
function envpm_process_request() {

	if ( envpm_environment_is_valid() ) {

		if ( !empty( $_REQUEST[ envpm_key_for_only() ] ) ) {
			envpm_add( envpm_option_key(), $_REQUEST[ envpm_key_for_only() ] );
		}
		elseif ( !empty( $_REQUEST[ envpm_key_for_not_only() ] ) ) {
			envpm_delete( envpm_option_key(), $_REQUEST[ envpm_key_for_not_only() ] );
		}

	}

}


/**
 * Adds an action link to all active plugins, except for this one.
 */
function envpm_add_links() {

	include_once( ABSPATH . '/wp-admin/includes/plugin.php' );

	$plugins = wp_get_active_and_valid_plugins();

	foreach ( $plugins as $plugin ) {

		if ( $plugin != __FILE__ ) { // You need this plugin to be always active in any environment.
			add_filter( 'plugin_action_links_' . plugin_basename( $plugin ), 'envpm_action_link', 10, 2 );
		}

	}
	
}


/**
 * Adds a link to the array of action links for the given plugin.
 * 
 * @param array $links The list of links.
 * @param string $plugin The basename of a plugin. 
 */
function envpm_action_link( $links, $plugin ) {

	$links[] = envpm_make_link( $plugin );

	return $links;

}


/**
 * Obtains values for the current environment and calls envpm_process_link() 
 * to return an option link for the given plugin.
 * 
 * This function is kind of repetitive. Maybe it could be improved in the future.
 * 
 * @param string $plugin The basename of a plugin.
 */
function envpm_make_link( $plugin ) {

	if ( envpm_environment_is_valid() ) {

		$current_environment = envpm_current_environment();

		if ( in_array( $plugin, envpm_get( envpm_option_key() ) ) ) {
			$key = envpm_key_for_not_only();
			$message = sprintf( __( 'No more %s only', 'envpm' ), $current_environment );
		}
		else {
			$key = envpm_key_for_only();
			$message = sprintf( __( 'Use for %s only', 'envpm' ), $current_environment );
		}

		$link = envpm_process_link( $plugin, $key, $message );
		return $link;

	}

	return false;

}


/**
 * Processes HTML for the current option link of a plugin.
 * 
 * @param string $plugin The path of a plugin.
 * @param string $key The GET key to process the link's request with.
 * @param string $message The message to display to the user.
 */
function envpm_process_link( $plugin, $key, $message ) {

	$link = '<a id="' . sanitize_title( $plugin ) . '" href="' . get_bloginfo( 'wpurl' ) . '/wp-admin/plugins.php?' . $key . '=' . plugin_basename( $plugin ) . '">' . $message . '</a>';
	return $link;

}


/**
 * Returns list of plugins for the current environment only.
 */
function envpm_environment_only_plugins() {

	$env_plugins = envpm_get( envpm_option_key() );
	return $env_plugins;

}


/**
 * Returns list of plugins that should not be active in the current environment.
 */
function envpm_non_environment_only_plugins() {

	$accepted_values = envpm_accepted_environment_values();
	$non_environment_only_plugins = array();

	foreach ( $accepted_values as $accepted_value ) {

		if ( $accepted_value != envpm_current_environment() ) {

			$plugins = envpm_get( envpm_option_key_with_environment( $accepted_value ) );

			if ( is_array( $plugins )  and !empty( $plugins ) ) {

				foreach ( $plugins as $plugin ) {
					$non_environment_only_plugins[] = $plugin;
				}

			}

		}

	}

	return $non_environment_only_plugins;

}


/**
 * Obtains values for the current environment and calls envpm_do_reset().
 */
function envpm_reset() {

	if ( envpm_environment_is_valid() ) {
		return envpm_do_reset( envpm_current_environment(), envpm_get( envpm_option_key() ), envpm_non_environment_only_plugins() );
	}

	return false;

}


/**
 * Resets the plugin configuration for a given environment.
 * 
 * @param string $environment The name of the environment.
 * @param array $env_plugins The list of plugins to activate.
 * @param array $non_env_plugins The list of plugins to deactivate.
 */
function envpm_do_reset( $environment, $env_plugins, $non_env_plugins ) {

	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

	try {
		deactivate_plugins( $non_env_plugins );
		activate_plugins( $env_plugins, '' );
		return true;
	}
	catch ( Exception $e ) {
		return false;
	}

}


/**
 * Obtains the list of plugins for a given environment.
 * 
 * @param string $option The name of the wp_option that stores the list of plugins for an environment.
 */
function envpm_get( $option ) {

	$plugins = get_option( $option );

	if ( !$plugins ) {
		add_option( $option, array() );
		$plugins = get_option( $option );
	}

	return $plugins;

}


/**
 * Adds a plugin from the list of a given environment.
 * 
 * @param string $option The name of the wp_option that stores the list of plugins for an environment.
 * @param string $value The basename of the plugin that must be added to the list.
 */
function envpm_add( $option, $plugin ) {

	$plugins = envpm_get( $option );

	// If the given plugin is not on the list of plugins, then we add it.
	if( !in_array( $plugin, $plugins ) ) {
		$plugins[] = $plugin;
	}

	// The modified array is the new list of plugins.
	envpm_update( $option, $plugins );

}


/**
 * Updates the list of plugins for a given environment.
 * 
 * @param string $option The name of the wp_option that stores the list of plugins for an environment.
 * @param array $value The updated value for the list.
 */
function envpm_update( $option, $value ) {

	if ( is_array( envpm_get( $option ) ) ) {
		update_option( $option, $value );
	}

}


/**
 * Removes a plugin from the list for a given environment.
 * 
 * @param string $option The name of the wp_option that stores the list of plugins for an environment.
 * @param string $value The basename of the plugin that must be removed from the list.
 */
function envpm_delete( $option, $value ) {

	$plugins = envpm_get( $option );

	// If the given plugin is on the list, then remove it.
	if( ( $key = array_search( $value, $plugins ) ) !== false ) {
	    unset( $plugins[$key] );
	}

	// The modified array is the new list of plugins.
	envpm_update( $option, $plugins );

}


/**
 * Javascript code for AJAX management of plugins.
 */
function envpm_add_script() {

	global $current_screen;

	if ( $current_screen->parent_base == 'plugins' ) {

		echo '<script type="text/javascript">

			jQuery( document ).ready( function() {

				jQuery(\'.row-actions-visible .0 a\').click( function( event ) {
					
					event.preventDefault();

					var link = this;
					var href = jQuery( link ).attr(\'href\');
					var id = jQuery( link ).attr(\'id\');
					var key = href.split(\'?\')[1].split(\'=\')[0];
					var value = href.split(\'?\')[1].split(\'=\')[1];

					var data = { action: \'envpm_process_ajax\', href: href, id: id, key: key, value: value }

					jQuery.post( ajaxurl, data, function( response ) {
						jQuery( link ).replaceWith( response );
					});

				} );
				
			} );

		</script>';

	}

}


/**
 * Callback for modifying plugins via AJAX.
 */
function envpm_process_ajax() {

	// We need to "mock" the request a little bit here.
	$_REQUEST[ $_POST['key'] ] = $_POST['value'];
	
	// Process requested plugin as needed.
	envpm_process_request();

	// Print new link for the processed plugin.
	echo envpm_make_link( $_POST['value'] );

	die(); // Required to return a proper result.

}


/**
 * Checks if this plugin is a must-use.
 */
function envpm_is_mu_plugin() {

	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	
	$mu_plugins = get_mu_plugins();

	return !empty( $mu_plugins[ basename( __FILE__ ) ] );

}


/**
 * Check if environments can be reset automatically.
 */
function envpm_has_auto_reset() {

	return ( defined( 'WP_ENV_PLUGINS_AUTO_RESET' ) and WP_ENV_PLUGINS_AUTO_RESET );

}


// Let's fire all this shit \m/
add_action( 'admin_notices', 'envpm_admin_warning' );
add_action( 'admin_bar_menu', 'envpm_reset_button', 50 );
add_action( 'plugins_loaded', 'envpm_process_request' );
add_action( 'plugins_loaded', 'envpm_add_links' );
add_action( 'admin_footer', 'envpm_add_script' );
add_action( 'wp_ajax_envpm_process_ajax', 'envpm_process_ajax' );

if ( envpm_has_auto_reset() ) {
	envpm_process_reset();
}
else {
	add_action( 'plugins_loaded', 'envpm_process_reset' );
}