<?php
/** 
 * ------------------------------------------------------------------------------
 * Plugin Name: Check Plugin Status
 * Description: Check the status of plugins on your site.
 * Version: 1.0.0
 * Author: azurecurve
 * Author URI: https://development.azurecurve.co.uk/classicpress-plugins/
 * Plugin URI: https://development.azurecurve.co.uk/classicpress-plugins/check-plugin-status/
 * Text Domain: check-plugin-status
 * Domain Path: /languages
 * ------------------------------------------------------------------------------
 * This is free sottware released under the terms of the General Public License,
 * version 2, or later. It is distributed WITHOUT ANY WARRANTY; without even the
 * implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. Full
 * text of the license is available at https://www.gnu.org/licenses/gpl-2.0.html.
 * ------------------------------------------------------------------------------
 */

// Prevent direct access.
if (!defined('ABSPATH')){
	die();
}

// include plugin menu
require_once(dirname(__FILE__).'/pluginmenu/menu.php');
add_action('admin_init', 'azrcrv_create_plugin_menu_cps');

// include update client
require_once(dirname(__FILE__).'/libraries/updateclient/UpdateClient.class.php');

/**
 * Setup registration activation hook, actions, filters and shortcodes.
 *
 * @since 1.0.0
 *
 */
// add actions
add_action('admin_menu', 'azrcrv_cps_create_admin_menu');
add_action('admin_enqueue_scripts', 'azrcrv_cps_load_admin_css');
add_action('plugins_loaded', 'azrcrv_cps_load_languages');

// add filters
add_filter('plugin_action_links', 'azrcrv_cps_add_plugin_action_link', 10, 2);
add_filter('codepotent_update_manager_image_path', 'azrcrv_cps_custom_image_path');
add_filter('codepotent_update_manager_image_url', 'azrcrv_cps_custom_image_url');

/**
 * Load language files.
 *
 * @since 1.0.0
 *
 */
function azrcrv_cps_load_languages() {
    $plugin_rel_path = basename(dirname(__FILE__)).'/languages';
    load_plugin_textdomain('check-plugin-status', false, $plugin_rel_path);
}

/**
 * Custom plugin image path.
 *
 * @since 1.0.0
 *
 */
function azrcrv_cps_custom_image_path($path){
    if (strpos($path, 'azrcrv-check-plugin-status') !== false){
        $path = plugin_dir_path(__FILE__).'assets/pluginimages';
    }
    return $path;
}

/**
 * Custom plugin image url.
 *
 * @since 1.0.0
 *
 */
function azrcrv_cps_custom_image_url($url){
    if (strpos($url, 'azrcrv-check-plugin-status') !== false){
        $url = plugin_dir_url(__FILE__).'assets/pluginimages';
    }
    return $url;
}

/**
 * Load css for admin page.
 *
 * @since 1.0.0
 *
 */
function azrcrv_cps_load_admin_css($hook){
	
	if ('azurecurve_page_azrcrv-cps' != $hook){ return; }

	wp_register_style('azrcrv-cps-admin-css', plugin_dir_url(__FILE__).'assets/css/admin.css', false, '1.0.0');
	wp_enqueue_style('azrcrv-cps-admin-css');

}

/**
 * Add action link on plugins page.
 *
 * @since 1.0.0
 *
 */
function azrcrv_cps_add_plugin_action_link($links, $file){
	static $this_plugin;

	if (!$this_plugin){
		$this_plugin = plugin_basename(__FILE__);
	}

	if ($file == $this_plugin){
		$settings_link = '<a href="'.admin_url('admin.php?page=azrcrv-cps').'"><img src="'.plugins_url('/pluginmenu/images/logo.svg', __FILE__).'" style="padding-top: 2px; margin-right: -5px; height: 16px; width: 16px;" alt="azurecurve" />'.esc_html__('Settings' ,'check-plugin-status').'</a>';
		array_unshift($links, $settings_link);
	}

	return $links;
}

/**
 * Add to menu.
 *
 * @since 1.0.0
 *
 */
function azrcrv_cps_create_admin_menu(){
	
	add_submenu_page("azrcrv-plugin-menu"
						,__("Check Plugin Status", "check-plugin-status")
						,__("Check Plugin Status", "check-plugin-status")
						,'manage_options'
						,'azrcrv-cps'
						,'azrcrv_cps_display_options');
}

/*
 * Display admin page for this plugin
 *
 * @since 1.0.0
 *
 */
function azrcrv_cps_display_options(){

	if (!current_user_can('manage_options')) {
		wp_die(__('You do not have sufficient permissions to access this page.', 'check-plugin-status'));
	}
	
	echo '<div id="azrcrv-cps-general" class="wrap azrcrv-cps">
		<fieldset>
			<h1>'.esc_html(get_admin_page_title()).'</h1>';
				
			$plugins = get_plugins();
			
			$check = array();
			foreach ($plugins as $key => $plugin){
				$check[$key] = array(
											Name => $plugin['Name'],
											Slug => azrcrv_cps_get_slug($key),
											PluginURI => $plugin['PluginURI'],
											Version => $plugin['Version'],
										);
			}
			
			
			echo '<table class="form-table">
				
				<tr>
					<th scope="row"><label for="widget-width">
						'.__('WordPress Plugins', 'check-plugin-status').'
					</th>
					<td>';
						if (!function_exists('plugins_api')){
							require_once(ABSPATH.'wp-admin/includes/plugin-install.php');
						}
						if (count($check) > 0){
							echo '<table class="azrcrv-cps">';
								echo '<tr>
									<th class="azrcrv-cps">Plugin Name</th>
									<th class="azrcrv-cps">Details</th>
								</tr>';
							foreach ($check as $key => $plugin){
								echo '<tr>
									<td class="azrcrv-cps">
										<a href="'.$plugin['PluginURI'].'">'.$plugin['Name'].'</a>
									</td>
									<td class="azrcrv-cps">';
									
										$slug_exceptions = array(
																	"wp-contact-form-7" => "contact-form-7",
																	"sitemap" => "google-sitemap-generator",
																);
										
										if (isset($slug_exceptions[$plugin['Slug']])){
											$slug = $slug_exceptions[$plugin['Slug']];
										}else{
											$slug = $plugin['Slug'];
										}
										
										$api = plugins_api('plugin_information', array(
																						'slug' => $slug,
																						'fields' => array(
																											'version' => true
																											, 'download_link' => true
																											, 'requires' => true
																										)
															));
										
										$plugin_response = '';
										if(!isset($api->version)){
											$plugin_response .= '<span class="azrcrv-cps-missing">'.__('Plugin status could not be determined.', 'check-plugin-status').'</span>';
										}else{
											if (isset($api->external)){
												$plugin_response .= '<span class="azrcrv-cps">'.__('Plugin has a 3rd party update mechanism.', 'check-plugin-status').'</span>';
											}else{
												$plugin_response .= '<span class="azrcrv-cps">'.__('Plugin is in the WordPress Repository', 'check-plugin-status').'</span>';
											}
											if ($api->requires > 4.9){
												$plugin_response .= '<br /><span class="azrcrv-cps-error">'.__('Not supported on ClassicPress', 'check-plugin-status').'</span>';
											}
										}
										$plugin_response .= '<br />';
										if ($plugin['Version'] == $api->version){
											$plugin_response .= __('Version match: ', 'check-plugin-status');
											$symbol = '=';
										}else{
											if ($api->version != ''){
												$plugin_response .= '<span class="azrcrv-cps-error">'.__('Version mismatch: ', 'check-plugin-status').'</span>';
												if ($plugin['Version'] < $api->version){
													$symbol = '<span class="azrcrv-cps-error">&lt;</span>';
												}else{
													$symbol = '<span class="azrcrv-cps-error">&gt;</span>';
												}
											}
										}
										if ($api->version <> ''){
											$plugin_response .= $plugin['Version'].' '.$symbol.' '.$api->version;
										}
										echo $plugin_response;
									echo '</td>
								</tr>';
							}
							echo '</table>';
						}else{
							_e('There are no plugins to check.', 'check-plugin-status');
						}
					echo '</td>
				</tr>
				
			</table>
				
		</fieldset>
	</div>';
}

function azrcrv_cps_get_slug($plugin_key){
	
	$plugin_slug = basename($plugin_key, '.php');
	
    return $plugin_slug;
	
}