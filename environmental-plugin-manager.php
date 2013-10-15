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
Version: 1.0
*/


/**
 * Displays a warning message.
 */
function envpm_admin_warning() {

	if ( !defined( 'WP_ENV_PLUGINS' ) ) {

		$html = '<div class="error"><p><strong>'. __( 'Warning', 'envpm' ) .':</strong> ' . __( 'Plugins-By-Environment Manager is active, but you have not defined the <code>WP_ENV_PLUGINS</code> constant. Please do it so and assign to it one of the following values in order for Plugins-By-Environment Manager to work: <code>development</code>, <code>staging</code>, <code>production</code>.', 'envpm' ) . '</p></div>';
	    
	    echo $html;

	}

}


/**
 * Displays a success message.
 */
function envpm_admin_success() {
	
	$html = '<div class="updated"><p>' . sprintf( __( 'The list of active plugins was updated to the %s environment.', 'envpm' ), WP_ENV_PLUGINS ). '</p></div>';
	    
	echo $html;

}


/**
 * Adds a button to the admin bar that allows to reset the environment for listed plugins.
 * 
 * @param object $wp_admin_bar The WordPress admin bar.
 */
function envpm_reset_button( $wp_admin_bar ) {

	if ( current_user_can( 'activate_plugins' ) and defined( 'WP_ENV_PLUGINS' ) and !is_network_admin() ) {

		$uri = $_SERVER['REQUEST_URI'];
		$separator = '';
		$key = '';

		if ( !stripos( $uri, 'reset_env_plugins' ) ) {
			$separator = stripos( $uri, '?' ) ? '&' : '?';
			$key = 'reset_env_plugins';
		}
		
		$args = array(
			'id' => 'env-plugins-reset-button',
			'title' => sprintf( __( 'Reset Plugins Environment (%s)', 'envpm' ), WP_ENV_PLUGINS ),
			'href' => $uri . $separator . $key,
			'parent' => 'top-secondary',
			'meta' => array(
				'class' => 'env-plugins-reset-button'
			)
		);
		
		$wp_admin_bar->add_node($args);

	}

}


/**
 * Sets up configuration for current environment.
 * Shows a notice if you are in an admin screen. 
 */
function envpm_init() {

	if ( isset( $_GET['reset_env_plugins'] ) and defined( 'WP_ENV_PLUGINS' ) ) {

		if ( envpm_setup() ) {
	
			if ( is_admin() ) {
				add_action( 'admin_notices', 'envpm_admin_success' );				
			}
	
		}

	}

}


/**
 * Processes the request for a plugin in order to add it or remove it 
 * from the list of plugins for the current environment.
 */
function envpm_process_request() {

	if ( isset( $_REQUEST ) ) {

		if ( defined( 'WP_ENV_PLUGINS' ) ) {

			switch ( WP_ENV_PLUGINS ) {

				case 'development':

					if ( isset( $_REQUEST['dev_only'] ) and $_REQUEST['dev_only'] ) {
						envpm_add( 'envpm_dev', $_REQUEST['dev_only'] );
					}
					elseif ( isset( $_REQUEST['not_dev_only'] ) and $_REQUEST['not_dev_only'] ) {
						envpm_delete( 'envpm_dev', $_REQUEST['not_dev_only'] );
					}

					break;

				case 'staging':

					if ( isset( $_REQUEST['stage_only'] ) and $_REQUEST['stage_only'] ) {
						envpm_add( 'envpm_stage', $_REQUEST['stage_only'] );
					}
					elseif ( isset( $_REQUEST['not_stage_only'] ) and $_REQUEST['not_stage_only'] ) {
						envpm_delete( 'envpm_stage', $_REQUEST['not_stage_only'] );
					}

					break;

				case 'production':
					
					if ( isset( $_REQUEST['prod_only'] ) and $_REQUEST['prod_only'] ) {
						envpm_add( 'envpm_prod', $_REQUEST['prod_only'] );
					}
					elseif ( isset( $_REQUEST['not_prod_only'] ) and $_REQUEST['not_prod_only'] ) {
						envpm_delete( 'envpm_prod', $_REQUEST['not_prod_only'] );
					}

					break;
				
				default:

					break;
			
			}

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

	if ( defined( 'WP_ENV_PLUGINS' ) ) {

		switch ( WP_ENV_PLUGINS ) {

			case 'development':

				if ( in_array( $plugin, envpm_get( 'envpm_dev' ) ) ) {
					$key = 'not_dev_only';
					$message = __( 'No more development only', 'envpm' );
				}
				else {
					$key = 'dev_only';
					$message = __( 'Use for development only', 'envpm' );
				}

				$link = envpm_process_link( $plugin, $key, $message );

				return $link;

				break;

			case 'staging':
				
				if ( in_array( $plugin, envpm_get( 'envpm_stage' ) ) ) {
					$key = 'not_stage_only';
					$message = __( 'No more staging only', 'envpm' );
				}
				else {
					$key = 'stage_only';
					$message = __( 'Use for staging only', 'envpm' );
				}

				$link = envpm_process_link( $plugin, $key, $message );

				return $link;

				break;

			case 'production':
				
				if ( in_array( $plugin, envpm_get( 'envpm_prod' ) ) ) {
					$key = 'not_prod_only';
					$message = __( 'No more production only', 'envpm' );
				}
				else {
					$key = 'prod_only';
					$message = __( 'Use for production only', 'envpm' );
				}

				$link = envpm_process_link( $plugin, $key, $message );

				return $link;

				break;

			default:

				break;

		}

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
 * Obtains values for the current environment and calls envpm_reset().
 */
function envpm_setup() {

	// Obtain values by current environment.
	if ( ( 'development' == WP_ENV_PLUGINS ) ) {

		$environment = 'development';
		$env_plugins = envpm_get( 'envpm_dev' );
		$non_env_plugins = ( $flag_value == 'production' ) ? envpm_get( 'envpm_prod' ) : envpm_get( 'envpm_stage' );
		
	}
	elseif ( ( 'staging' == WP_ENV_PLUGINS ) ) {

		$environment = 'staging';
		$env_plugins = envpm_get( 'envpm_stage' );
		$non_env_plugins = ( $flag_value == 'production' ) ? envpm_get( 'envpm_prod' ) : envpm_get( 'envpm_dev' );
		
	}
	elseif ( ( 'production' == WP_ENV_PLUGINS ) ) {

		$environment = 'production';
		$env_plugins = envpm_get( 'envpm_prod' );
		$non_env_plugins = ( $flag_value == 'staging' ) ? envpm_get( 'envpm_stage' ) : envpm_get( 'envpm_dev' );
		
	}

	return envpm_reset( $environment, $env_plugins, $non_env_plugins );

}


/**
 * Resets the plugin configuration for a given environment.
 * 
 * @param string $environment The name of the environment.
 * @param array $env_plugins The list of plugins to activate.
 * @param array $non_env_plugins The list of plugins to deactivate.
 */
function envpm_reset( $environment, $env_plugins, $non_env_plugins ) {

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

					var data = {
						action: \'envpm_process_ajax\',
						href: href,
						id: id,
						key: key,
						value: value
					}

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


// Let's fire all this shit \m/
add_action( 'admin_notices', 'envpm_admin_warning' );
add_action( 'admin_bar_menu', 'envpm_reset_button', 50 );
add_action( 'plugins_loaded', 'envpm_init' );
add_action( 'plugins_loaded', 'envpm_process_request' );
add_action( 'plugins_loaded', 'envpm_add_links' );
add_action( 'admin_footer', 'envpm_add_script' );
add_action( 'wp_ajax_envpm_process_ajax', 'envpm_process_ajax' );