<?php
/*
Plugin Name:		Maintenance & Coming Soon Redirect Animation
Plugin URI:			https://wordpress.org/plugins/maintenance-coming-soon-redirect-animation/
Description:		Make your website in maintenance mode in seconds with great looking animations and configure settings to allow specific users to bypass the maintenance mode.
Version:			2.4.1
Stable tag:		 		2.4.1
Requires at least:	5.0
Tested up to:		7.2
Requires PHP:		7.4

Text Domain: 		maintenance-coming-soon-redirect-animation
Domain Path: 		/languages

License:			GPLv3
License URI:		https://www.gnu.org/licenses/gpl-3.0.html

Author:				Yassine Idrissi 
Author URI:			https://profiles.wordpress.org/ilyasine/

Copyright:			2022 Yassine Idrissi	(email: ilyasine@outlook.be)
						

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License, version 3, as
	published by the Free Software Foundation.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

    

*/

// Exit if accessed directly

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/blocks/animation-block/block.php';

if( !class_exists("wploti_maintenance_redirect") ) {

	class wploti_maintenance_redirect {
		
		var $admin_options_name;
		var $maintenance_html;
		var $maintenance_head;
		private $console;
		private $console_style;		
		private $headers = null;		
		/**
		 * (php) constructor
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */

		private function get_headers() {
			if ($this->headers === null) {
				$this->headers = [
					[
						'title' => __('200 OK', 'maintenance-coming-soon-redirect-animation'),
						'code' => '200',
						'description' => __('Best used for when the site is under development.', 'maintenance-coming-soon-redirect-animation')
					],
					[
						'title' => __('202 Accepted', 'maintenance-coming-soon-redirect-animation'),
						'code' => '202',
						'description' => __('The request has been accepted for processing, but the processing has not been completed.', 'maintenance-coming-soon-redirect-animation')
					],
					[
						'title' => __('206 Partial Content', 'maintenance-coming-soon-redirect-animation'),
						'code' => '206',
						'description' => __('The request has succeeded and the body contains the requested ranges of data, as described in the Range header of the request.', 'maintenance-coming-soon-redirect-animation')
					],
					[
						'title' => __('302 Found', 'maintenance-coming-soon-redirect-animation'),
						'code' => '302',
						'description' => __('The target resource resides temporarily under a different URI', 'maintenance-coming-soon-redirect-animation')
					],
					[
						'title' => __('307 Temporary Redirect', 'maintenance-coming-soon-redirect-animation'),
						'code' => '307',
						'description' => __('The resource requested has been temporarily moved to a different URI', 'maintenance-coming-soon-redirect-animation')
					],
					[
						'title' => __('503 Service Temporarily Unavailable', 'maintenance-coming-soon-redirect-animation'),
						'code' => '503',
						'description' => __('Best for when the site is temporarily taken offline for small amendments. If used for a long period of time, 503 can damage your Google ranking.', 'maintenance-coming-soon-redirect-animation')
					],
				];
			}

			return $this->headers;
		}



		/**
		 * (php) initialize
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */
		
		public function init() {
			global $wpdb, $wploti_whitelisted_roles;		
			
			// Create keys table if needed
			$tbl = $wpdb->prefix . $this->admin_options_name . "_access_keys";

			// Check if table exists using esc_like() for safe table name comparison
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->esc_like($tbl))) != $tbl) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.SchemaChange
				$sql = "CREATE TABLE `" . esc_sql($tbl) . "` (
					id INT AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(100),
					access_key VARCHAR(20),
					email VARCHAR(100),
					created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
					active INT(1) NOT NULL DEFAULT 1
				)";

				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query($sql);
			}

			// Create IPs table if needed
			$tbl = $wpdb->prefix . $this->admin_options_name . "_unrestricted_ips";

			// Check if table exists using esc_like() for safe table name comparison
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $wpdb->esc_like($tbl))) != $tbl) {
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange
				$sql = "CREATE TABLE `" . esc_sql($tbl) . "` (
					id INT AUTO_INCREMENT PRIMARY KEY,
					name VARCHAR(100),
					ip_address VARCHAR(45),
					created_at DATETIME NOT NULL DEFAULT '0000-00-00 00:00:00',
					active INT(1) NOT NULL DEFAULT 1
				)";

				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, PluginCheck.Security.DirectDB.UnescapedDBParameter
				$wpdb->query($sql);
			}
			
			$wploti_whitelisted_roles = array('administrator');
			$wploti_whitelisted_users = array();
			$wploti_message = __( 'This site is currently undergoing maintenance. Please check back later', 'maintenance-coming-soon-redirect-animation' );

			// setup options
			
			add_option('wploti_animation', wploti_animation_dir . 'default-animation.json');
				
			update_option('wploti_activation_notice', 1);
			update_option('wploti_notes_notice', 1 );
			update_option('wploti_status', '0');
			update_option('wploti_header_type', '200');
			
			update_option('wploti_whitelisted_roles', $wploti_whitelisted_roles);						
			update_option('wploti_whitelisted_users', $wploti_whitelisted_users);		
			update_option('wploti_message', $wploti_message);
			update_option( 'wploti_custom_login_enabled', '0' );
			update_option( 'wploti_custom_login_slug', '' );
			update_option( 'wploti_private_login_enabled', '1' );
			
		}

		/**
		 * Ensure unrestricted IP storage supports IPv6 addresses.
		 *
		 * @return void
		 */
		public function upgrade_unrestricted_ips_table() {
			global $wpdb;

			$tbl = $wpdb->prefix . $this->admin_options_name . '_unrestricted_ips';
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->query( 'ALTER TABLE `' . esc_sql( $tbl ) . '` MODIFY ip_address VARCHAR(45)' );
		}

		public function init_hooks(){
			$wploti_header = get_option('wploti_header_type');
			$wploti_headers = $this->get_headers();

			if (headers_sent()) {
				return;
			}

			foreach($wploti_headers as $header) {				

					if ($wploti_header === $header['code']) :
				
						$this->console = $header['code'] === '503' ? $header['title'] : $header['description'];
						$this->console_style = '';
						$this->console_style .= 'margin: 20px auto;font-family: cursive;';
						$this->console_style .= 'font-size: 30px; font-weight: bold;';
						$this->console_style .= 'color: #CFC547; text-align: center;';
						$this->console_style .= 'letter-spacing: 5px;';
						$this->console_style .= 'text-shadow: 3px 0px 2px rgba(81,67,21,0.8), -3px 0px 2px rgba(81,67,21,0.8),0px 4px 2px rgba(81,67,21,0.8);';


						if ( !is_admin() ) :
							header('HTTP/1.1 ' . $header['title'] );
							header('Status: ' . $header['title'] );							
							header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
							header("Pragma: no-cache"); // HTTP 1.0.
							header("Expires: 0"); // Proxies.
							header('Retry-After: 600');
						endif;

					endif;

			}
		}
	
		/**
		 * (php) add custom class to plugin settings page
		 *
		 * @since 2.0.0
		 * @access public
		 * @return string
		 */
		
		public function wploti_body_class($classes) {

			$wploti_current_screen = get_current_screen();

			if ($wploti_current_screen->base === "toplevel_page_wploti-settings") 
			
				$classes .= ' wploti_settings_page';
								
			return $classes;
		}


		/**
		 * (php) Get input message value
		 *
		 * @since 1.0.0
		 * @access public
		 * @return string
		 */


		public function wploti_ajax_message() {

			global $refresh_active;

			if ( isset($_POST['message']) ) {

				check_ajax_referer('wploti_nonce', 'security');

				$wploti_message = wp_kses_post(wp_unslash($_POST['message']));

				update_option('wploti_message', $wploti_message);

				wp_send_json_success(array('message' => __('Message saved.', 'maintenance-coming-soon-redirect-animation')));

			}

			wp_send_json_error(array('message' => __('Message not saved.', 'maintenance-coming-soon-redirect-animation')));

			wp_die();

		}
		

		/**
		 * (php) Update header type
		 *
		 * @since 1.0.0
		 * @access public
		 * @return string
		 */

		public function wploti_ajax_header_type(){

			check_ajax_referer('wploti_nonce', 'security');

			if ( isset($_POST['header_type']) ) {

				$header_type = sanitize_text_field(wp_unslash($_POST['header_type']));

				update_option('wploti_header_type', $header_type);

				wp_send_json_success(array('message' => __('Header Type saved.', 'maintenance-coming-soon-redirect-animation')));
				
			}
			
			wp_send_json_error(array('message' => __('Header Type not saved.', 'maintenance-coming-soon-redirect-animation')));
			
			wp_die();
		
		}

		/**
		 * Dismiss activation notice
		 *
		 * @since 1.1.1
		 * @access public
		 * @return void
		 */
		
		public function wploti_ajax_dismiss_activation_notice() {

			check_ajax_referer( 'wploti_nonce', 'security' );

			// user has dismissed the welcome notice
			update_option( 'wploti_activation_notice', 0 );

			wp_die();
			
		}

		/**
		 * Dismiss notes notice
		 *
		 * @since 1.1.1
		 * @access public
		 * @return void
		 */
		
		public function wploti_ajax_dismiss_notes_notice() {

			check_ajax_referer( 'wploti_nonce', 'security' );

			// user has dismissed the notes notice
			update_option( 'wploti_notes_notice', 0 );

			wp_die();
			
		}

		/**  (php) show Maintenance Mode notice on WP login form
		* 
		* @since 2.0.0
		* @access public
		* @return void
		*/ 

		public function login_message($message)
		{
			if ( $this->wploti_active() == '1') {
				$message .= '<div class="message">' . __('Maintenance Mode is <b>enabled</b>.', 'maintenance-coming-soon-redirect-animation') . '</div>';
			}
	
			return $message;
		} 

		/**
		 * (php)  add settings link to plugin page
		 *
		 * @since 1.0.0
		 * @version 2.0.0 updated
		 * @access public
		 * @return array
		 */	

		public function wploti_action_links ( $actions ) {

			global $page , $plugin_file , $context , $s , $plugin_data , $plugin_id_attr;
			$new_actions = array();
			$new_actions[] = sprintf( '<a href="'.wploti_admin_url.'">%s</a>', __('Settings', 'maintenance-coming-soon-redirect-animation') );
			$new_actions = array_merge($new_actions, $actions);
			//$uninstall_url =admin_url() .'uninstall.php'.'&amp;action=uninstall&amp;_wpnonce='.wp_create_nonce('wploti_uninstall_'.get_current_user_id().'_wpnonce');
			//$new_actions[] = '<span class="delete"><a href="'.$uninstall_url.'" class="delete">'.__('Uninstall','=maintenance-coming-soon-redirect-animation').'</a></span>';
			/* if ( current_user_can( 'delete_plugins' ) && ! is_plugin_active( $plugin_file ) ) {
				$new_actions['delete'] = sprintf(
					'<a href="%s" id="delete-%s" class="delete" aria-label="%s">%s</a>',
					wp_nonce_url( 'plugins.php?action=delete-selected&amp;checked[]=' . urlencode( $plugin_file ) . '&amp' ),
					esc_attr( $plugin_id_attr ),
					
					esc_attr( sprintf( _x( 'Delete %s', 'plugin' ), $plugin_data['Name'] ) ),
					__( 'Delete' )
				);
			} */
			return $new_actions;
		}

		
		/**
		 * Filters the array of row meta for each plugin in the Plugins list table.
		 *
		 * @param string[] $plugin_meta An array of the plugin's metadata.
		 * @param string   $plugin_file Path to the plugin file relative to the plugins directory.
		 * @return string[] An array of the plugin's metadata.
		 */
		public function wploti_plugin_row_meta( array $plugin_meta, $plugin_file ) {
			if ( 'maintenance-coming-soon-redirect-animation/wploti_maintenance_redirect.php' !== $plugin_file ) {
				return $plugin_meta;
			}

			$plugin_meta[] = sprintf(
				'<a href="%1$s">%2$s</a>',
				'https://wordpress.org/support/plugin/maintenance-coming-soon-redirect-animation/',
				esc_html_x( 'Support', 'verb', 'maintenance-coming-soon-redirect-animation' )
			);
			$plugin_meta[] = sprintf(
				'<a href="%1$s"><span class="dashicons dashicons-star-filled" aria-hidden="true" style="font-size:14px;line-height:1.3"></span>%2$s</a>',
				'https://www.paypal.me/ilyasine1',
				esc_html_x( 'Sponsor', 'verb', 'maintenance-coming-soon-redirect-animation' )
			);

			return $plugin_meta;
		}

		/**  (php) display admin topbar notice
		* 
		* @since 1.1.1 
		* @version 2.0.0 updated
		* @access public
		* @return void
		*/ 

		public function wploti_admin_bar(){

			global $wp_admin_bar;
			$wploti_ajax_nonce = wp_create_nonce( "wploti_nonce" );
			$wploti_settings_img = '<div class="wploti_settings_img svg" style="background-image: url(&quot;'. wploti_icon .'&quot;) !important;" aria-hidden="true"><br></div>';
			$wploti_state_image = '<div class="wploti_animation_state"><lottie-player autoplay="true" loop src="'. esc_attr ( $this->wploti_active() == '1' ? IMG_path .'/green-on.json' : IMG_path .'/red-off.json'  ).'"  class="animation-state"></lottie-player></div>';
			$topbar = $wploti_state_image.'<div class="wploti_menu_text">'. __( "Maintenance Status" , "maintenance-coming-soon-redirect-animation" ) .' : </div><div class="toggle-wrapper"><div id="wploti-status-menubar" class="toggle-checkbox"></div><div id="wploti-toggle-adminbar" class="status-' . esc_attr( $this->wploti_active() ) . '" data-security="'. esc_attr($wploti_ajax_nonce) .'"><span class="toggle_handler"></span></div></div>';

	    	//Add the main siteadmin menu item
	        $wp_admin_bar->add_menu( array(
	            'id'     => 'wploti-activation-status',
				'title'  => $topbar,
	            'href'   => admin_url().'admin.php?page=wploti-settings',
	            'parent' => 'top-secondary',
	        ) );

			$wp_admin_bar->add_node(array(
				'id'     => 'wploti-preview',
				'title'  => '<span class="wploti_preview dashicons dashicons-external"></span>' . esc_attr__('Preview', 'maintenance-coming-soon-redirect-animation'),
				'meta'   => array('target' => 'blank'),
				'href'   => get_home_url() . '/?wploti_preview',
				'parent' => 'wploti-activation-status'
			));
			$wp_admin_bar->add_node(array(
				'id'     => 'wploti-settings',
				'title'  =>  $wploti_settings_img . esc_attr__('Settings', 'maintenance-coming-soon-redirect-animation'),
				'href'   => admin_url('admin.php?page=wploti-settings'),
				'parent' => 'wploti-activation-status',			
			));
		}

		/**  (php)  toggle activation for admin menu icon
		* 
		* @since 1.1.1
		* @access public
		* @return void
		*/ 

		public function wploti_ajax_toggle_activation() {
			
			// check for ajax payoload
			if ( isset( $_POST['payload'] )  && $_POST['payload'] == 'toggle_wploti_status' ) {

				// verify nonce
				check_ajax_referer( 'wploti_nonce' ,'security');

				// verify user rights
				if ( ! current_user_can('manage_options') ) {
					wp_die("Oh no you don't!");
					return;
				}

				if ( $this->wploti_active() === '0' ) {
					update_option('wploti_status', '1');	
				} else {
					update_option('wploti_status', '0');
				}

				wp_die();
				
			}
		}
		
		
		/**
		 * (php) add top-level administrative menu
		 *
		 * @since 1.0.0
		 * @return void
		 */
	
		public function wploti_maintenance_redirect_menu() {

			global $submenu;
	
			add_menu_page(
				__( 'Maintenance redirect Settings', 'maintenance-coming-soon-redirect-animation' ),
				__( 'Maintenance', 'maintenance-coming-soon-redirect-animation' ),
				'manage_options',
				'wploti-settings',
				[$this,'print_admin_page'],
				 wploti_icon,
				2
			);

			$wploti_menus = array(
				'header' => __( 'Header Type', 'maintenance-coming-soon-redirect-animation' ),
				'ip' => __( 'Unrestricted IP addresses ', 'maintenance-coming-soon-redirect-animation' ),				
				'keys' => __( 'Access Keys', 'maintenance-coming-soon-redirect-animation' ),
				'password' => __( 'Password', 'maintenance-coming-soon-redirect-animation' ),
                'animation' => __( 'Animation', 'maintenance-coming-soon-redirect-animation' ), 
                'message' => __( 'Message', 'maintenance-coming-soon-redirect-animation' ), 
                'extra' => __( 'Extra', 'maintenance-coming-soon-redirect-animation' ),
			);

			/** Register submenus */
			foreach( $wploti_menus as $menu_key => $menu_label ) {
				add_submenu_page( 
					'wploti-settings',
					$menu_label , 
					$menu_label , 
					'manage_options', 
					'wploti-settings#' . $menu_key , 
					array( $this,'print_admin_page' ),
				);
			}

			$submenu['wploti-settings'][0] = array( esc_html__( 'All settings', 'maintenance-coming-soon-redirect-animation' ), 'manage_options', 'wploti-settings', 'Maintenance redirect Settings' );

		}

		/**
		 * Animation Select
		 *
		 * @since 1.0.1
		 * @access public
		 * @return void
		 */

		public function animation_select(){

			if (isset($_POST['payload']) && $_POST['payload'] == 'animation_select_payload' && 
				isset($_POST['animation_url']) && !empty($_POST['animation_url'])) {
		
				check_ajax_referer('animation_select_nonce', 'security'); 

				$selected_animation =  basename(sanitize_text_field(wp_unslash($_POST['animation_url'])));

				update_option('wploti_animation', wploti_animation_dir . $selected_animation);
			}

			wp_die();

		}

		/**
		 * (php) load animations by infinite scroll
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */	

		public function load_animations(){
			
			if (isset($_POST['start'], $_POST['limit'], $_POST['payload']) && $_POST['payload'] == 'load_animations_payload'){ 

				check_ajax_referer('wploti_nonce', 'security'); 

				$animations = array_slice(scandir(__DIR__.'/animations'),2);
				$animation_select_nonce = wp_create_nonce('animation_select_nonce');
				$counter = sanitize_text_field(wp_unslash($_POST['start']));
				$limit = sanitize_text_field(wp_unslash($_POST['limit'])) ;
					
				while ( $counter < $limit  )  :   ?>
					<div class="animation-grid">
						<div id="lottiecontainer">
						<?php if (explode( wploti_animation_dir , get_option("wploti_animation"))[1] == $animations[$counter] ) : ?>
							<div class="selected-bg">
								<span class="selected-btn wp-core-ui button-secondary"><?php esc_html_e('Selected', 'maintenance-coming-soon-redirect-animation'); ?></span>
							</div>
						<?php endif; ?>										
							<div class="select-bg">
								<span class="select-btn wp-core-ui button-primary" data-security="<?php echo esc_attr($animation_select_nonce); ?>">
									<?php esc_html_e('Select', 'maintenance-coming-soon-redirect-animation') ?>
								</span>
							</div>										
							<lottie-player autoplay="true" loop src="<?php echo esc_attr(wploti_animation_dir .$animations[esc_attr($counter)]) ?>" class="lottieanimation"></lottie-player>
						</div>
					</div>
					<?php 

					$counter++;

				endwhile;
				
			 } ?>

				<script>
				jQuery(document).ready(function($) {

						$('.select-btn').click(function(){	

							animation = $(this).parent().next('lottie-player') ;

							var security = $(this).data('security');

							animation_url = animation.get(0).src ;

							selected_bg = '<div class="selected-bg"><span class="selected-btn wp-core-ui button-secondary"><?php esc_html_e('Selected', 'maintenance-coming-soon-redirect-animation'); ?></span></div>';

							$('.selected-animation > lottie-player').remove();

							animation.clone().appendTo( ".selected-animation" );

							$('.selected-bg').remove();

							$(this).parents("#lottiecontainer").prepend(selected_bg);

							$('.selected-animation').find('lottie-player').attr('src', animation_url)

							$.ajax({
								url: ajaxurl,
								data: {
									action: 'wploti_animation_select',
									animation_url: animation_url,
									security: security,
									payload: 'animation_select_payload',
								},
								type: 'post',

								success: function (result, textstatus) {
									$(".updated").fadeIn(1000).delay(7000).fadeOut("slow");
									/* console.log(result);
									console.log('sucess'); */
								},
								error: function (result) {
									/* console.log(result);
									console.log('fail'); */
								},

							})

						})
				});				
				</script>

			<?php wp_die();
		}


		/**
		 * (php) deactivate action
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */

		public function wploti_deactivate(){
			
			update_option('wploti_activation_notice', 0);
		}

		public function render_custom_login_page() {
			if ( '1' !== $this->wploti_active() ) {
				return;
			}

			global $wp;

			if ( '1' === get_option( 'wploti_custom_login_enabled', '0' ) && $this->get_custom_login_slug() && $this->get_custom_login_slug() === trim( $wp->request, '/' ) ) {
				// wp-login.php's own functions (e.g. login_header()) read these via `global`; without redeclaring them here they'd only be locals of this method, causing "undefined variable" notices.
				global $error, $user_login, $interim_login, $customize_login;
				require ABSPATH . 'wp-login.php';
				exit;
			}
		}

		public function restrict_default_login_url() {
			if ( '1' !== $this->wploti_active() || '1' !== get_option( 'wploti_custom_login_enabled', '0' ) ) {
				return;
			}

			// When wp-login.php is required from render_custom_login_page(), $wp->request still holds the custom slug; don't redirect that legitimate case.
			global $wp;
			if ( isset( $wp->request ) && $this->get_custom_login_slug() === trim( $wp->request, '/' ) ) {
				return;
			}

			wp_safe_redirect( home_url( '/' ) );
			exit;
		}

		private function get_custom_login_slug() {
			return sanitize_title_with_dashes( (string) get_option( 'wploti_custom_login_slug', '' ) );
		}

		public function filter_login_url( $login_url, $redirect, $force_reauth ) {
			if ( '1' !== $this->wploti_active() || '1' !== get_option( 'wploti_custom_login_enabled', '0' ) || ! $this->get_custom_login_slug() ) {
				return $login_url;
			}

			$custom_login_url = home_url( '/' . $this->get_custom_login_slug() . '/' );
			if ( $redirect ) {
				$custom_login_url = add_query_arg( 'redirect_to', rawurlencode( $redirect ), $custom_login_url );
			}
			if ( $force_reauth ) {
				$custom_login_url = add_query_arg( 'reauth', '1', $custom_login_url );
			}

			return $custom_login_url;
		}

		/**
		 * wp-login.php's own forms post to site_url( 'wp-login.php', 'login_post' ), not wp_login_url();
		 * without rewriting that too, submissions bypass our custom slug and hit restrict_default_login_url().
		 */
		public function filter_login_post_url( $url, $path, $scheme, $blog_id ) {
			if ( 'login_post' !== $scheme || 0 !== strpos( (string) $path, 'wp-login.php' ) ) {
				return $url;
			}
			if ( '1' !== $this->wploti_active() || '1' !== get_option( 'wploti_custom_login_enabled', '0' ) || ! $this->get_custom_login_slug() ) {
				return $url;
			}

			$query = '';
			$query_pos = strpos( $path, '?' );
			if ( false !== $query_pos ) {
				$query = substr( $path, $query_pos );
			}

			return home_url( '/' . $this->get_custom_login_slug() . '/' . $query );
		}

		public function save_custom_login_url() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'message' => __( 'You do not have access to this resource.', 'maintenance-coming-soon-redirect-animation' ) ) );
			}
			check_ajax_referer( 'wploti_nonce', 'security' );

			$enabled = isset( $_POST['enabled'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['enabled'] ) );
			$slug = isset( $_POST['slug'] ) ? sanitize_title_with_dashes( wp_unslash( $_POST['slug'] ) ) : '';
			if ( $enabled && ! $slug ) {
				wp_send_json_error( array( 'message' => __( 'Enter a login URL.', 'maintenance-coming-soon-redirect-animation' ) ) );
			}

			$private_login_enabled = isset( $_POST['private_login_enabled'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['private_login_enabled'] ) );

			update_option( 'wploti_custom_login_enabled', $enabled ? '1' : '0' );
			update_option( 'wploti_custom_login_slug', $slug );
			update_option( 'wploti_private_login_enabled', $private_login_enabled ? '1' : '0' );
			wp_send_json_success( array( 
				'slug' => $slug,
				'message' => $enabled ? __( 'Login Settings saved.', 'maintenance-coming-soon-redirect-animation' ) : __( 'Direct wp-login.php access restored.', 'maintenance-coming-soon-redirect-animation' ),
				'login_url_saved' => __('Login Settings saved.', 'maintenance-coming-soon-redirect-animation'),
				'login_access_restored' => __('Direct wp-login.php access restored.', 'maintenance-coming-soon-redirect-animation'),				
			) );
		}

		/**
		 * (php) enqueue admin style and script
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */

		public function wploti_enqueue_style_and_script_admin() {
	
			$style_src = plugin_dir_url( __FILE__ ) .'css/admin-style.css';

			$admin_bar_style_src = plugin_dir_url( __FILE__ ) .'css/wploti-admin-bar.css';

			$loti_script_src = plugin_dir_url( __FILE__ ) .'js/lottie-player-script.js';

			$select_slyle_src = plugin_dir_url( __FILE__ ) .'css/select2.min.css';

			$select_script_src = plugin_dir_url( __FILE__ ) .'js/select2.min.js';

			$admin_script_src = plugin_dir_url( __FILE__ ) .'js/admin-script.js';

			wp_enqueue_style( 'wploti-admin-style', $style_src, array(), WPLOTI_VERSION, 'all' );		

			wp_enqueue_style( 'admin-bar-style-src', $admin_bar_style_src, array(), WPLOTI_VERSION, 'all' );

			wp_enqueue_style( 'select-slyle-src', $select_slyle_src, array(), WPLOTI_VERSION, 'all' );

			wp_enqueue_script( 'select-script', $select_script_src, array(), WPLOTI_VERSION, false );

			wp_enqueue_script( 'lottiplayer-script', $loti_script_src, array(), WPLOTI_VERSION, false );

			wp_enqueue_script( 'admin-script', $admin_script_src, array('lottiplayer-script','select-script'), WPLOTI_VERSION, false );

			wp_enqueue_script('jquery-ui-tabs');
			wp_enqueue_script( 'wp-util' );
			wp_enqueue_script( 'password-strength-meter' );

			// Add the console warning as inline script
			if (isset($this->console) && isset($this->console_style)) {
				$inline_script = sprintf(
					'console.warn("%%c %s", "%s");',
					esc_js($this->console),
					esc_js($this->console_style)
				);
				wp_add_inline_script('lottiplayer-script', $inline_script);
			}

		}

		/**
		 * (php) enqueue public style and script
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */

		public function wploti_enqueue_style_and_script_public() {

			$loti_script_src = plugin_dir_url( __FILE__ ) .'js/lottie-player-script.js';

			$admin_bar_style_src = plugin_dir_url( __FILE__ ) .'css/wploti-admin-bar.css';
	
			wp_enqueue_style( 'admin_bar_style_src', $admin_bar_style_src, array(), WPLOTI_VERSION, 'all' );
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only flag, only used to decide which assets to load.
			if ( isset( $_GET['wploti_mr_maintenance_page'] ) ) {
				wp_enqueue_style( 'wploti-front-style', plugin_dir_url( __FILE__ ) . 'css/front-style.css', array(), WPLOTI_VERSION, 'all' );
			}

			wp_enqueue_script( 'lottiplayer-script', $loti_script_src, array(), WPLOTI_VERSION, false );
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only flag, only used to decide which assets to load.
			if ( isset( $_GET['wploti_mr_maintenance_page'] ) ) {
				wp_enqueue_script( 'wploti-front-panel', plugin_dir_url( __FILE__ ) . 'js/front-panel.js', array( 'jquery' ), WPLOTI_VERSION, true );
			}

			// Add the console warning as inline script
			if (isset($this->console) && isset($this->console_style)) {
				$inline_script = sprintf(
					'console.warn("%%c %s", "%s");',
					esc_js($this->console),
					esc_js($this->console_style)
				);
				wp_add_inline_script('lottiplayer-script', $inline_script);
			}

		}

		/**
		 * (php) load translation files
		 *
		 * @since 1.1.2
		 * @access public
		 * @return void
		 */
		
		public function wploti_translation() {

			// load_plugin_textdomain( 'maintenance-coming-soon-redirect-animation', false, dirname( plugin_basename( __FILE__ ) ) . '/languages/' );
			// No-op: translations for wp.org-hosted plugins are loaded automatically since WP 4.6.

		}

		/**
		 * (php) handle external translation files
		 *
		 * @since 1.1.2
		 * @access public
		 * @return void
		 */
		
		public function wploti_translations_script() {
			global $wploti_ajax_nonce, $wploti_message, $refresh_active;
	
			// variables to js
			$wploti_var = array(
				'IMG_path' => plugin_dir_url( __FILE__ ) .'images',
				'wploti_nonce' => esc_attr($wploti_ajax_nonce) ,
				'wploti_msg_value' => esc_attr( get_option('wploti_message', $wploti_message) ) ,			
				'be_careful' => esc_html__( 'Please Be careful', 'maintenance-coming-soon-redirect-animation' ),
				'option_reset_txt' => esc_html__( 'This option will reset all your selections to defaults options and will delete the IP addresses and access keys as well', 'maintenance-coming-soon-redirect-animation' ),
				'pls_wait' => esc_html__( 'Please Wait', 'maintenance-coming-soon-redirect-animation' ),
				'save_content' => esc_html__( 'Save Content', 'maintenance-coming-soon-redirect-animation' ),
				'saved_content' => esc_html__( 'Content Saved', 'maintenance-coming-soon-redirect-animation' ),
				'wploti_whitelisted_users_placeholder' => esc_attr__('Select whitelisted user(s)', 'maintenance-coming-soon-redirect-animation'),
				'ip_ajax_loader' => esc_url( plugins_url( 'images/ajax_loader_16x16.gif', __FILE__ ) ),
				'ip_name_required' => esc_html__( 'You must enter a Name.', 'maintenance-coming-soon-redirect-animation' ),
				'ip_required' => esc_html__( 'You must enter an IP.', 'maintenance-coming-soon-redirect-animation' ),
				'ip_invalid' => esc_html__( 'IP address not valid.', 'maintenance-coming-soon-redirect-animation' ),
				'ip_database_error' => esc_html__( 'There was a database error. Please reload this page.', 'maintenance-coming-soon-redirect-animation' ),
				'delete_ip_confirmation' => esc_html__( 'You are about to delete the IP address:', 'maintenance-coming-soon-redirect-animation' ),
				'enable' => esc_html__( 'Enable', 'maintenance-coming-soon-redirect-animation' ),
				'disable' => esc_html__( 'Disable', 'maintenance-coming-soon-redirect-animation' ),
				'save' => esc_html__( 'Save', 'maintenance-coming-soon-redirect-animation' ),
				'cancel' => esc_html__( 'Cancel', 'maintenance-coming-soon-redirect-animation' ),
				'page_exclusion_error' => esc_html__( 'Unable to update the maintenance exclusion. Please reload this page.', 'maintenance-coming-soon-redirect-animation' ),
				'maintenance_page_error' => esc_html__( 'Unable to update the maintenance page. Please reload this page.', 'maintenance-coming-soon-redirect-animation' ),
				'make_maintenance_page' => esc_html__( 'Make maintenance page', 'maintenance-coming-soon-redirect-animation' ),
			);

			// Localize the script with new data
			wp_localize_script( 'admin-script', 'wploti_var', $wploti_var );

		}

		/**
		 * (php) find user IP
		 *
		 * @since 1.0.0
		 * @access public
		 * @return string
		 */

		public function get_user_ip() {
			// Check if HTTP_X_FORWARDED_FOR is set and unslash it
			if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
			} 
			// Fallback to REMOTE_ADDR if HTTP_X_FORWARDED_FOR is not set
			elseif ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
				$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
			} 
			// If neither is set, return a default value (e.g., 'unknown')
			else {
				$ip = 'unknown';
			}
		
			return $ip;
		}

		/**
		 * Get the IPv4 and IPv6 addresses supplied by the current request.
		 *
		 * @return array
		 */
		public function get_user_ips() {
			$ips = array(
				'ipv4' => '',
				'ipv6' => '',
			);
			$ip_sources = array();

			if ( isset( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
				$ip_sources[] = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
			}
			if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
				$ip_sources[] = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
			}

			foreach ( $ip_sources as $ip_source ) {
				foreach ( explode( ',', $ip_source ) as $ip ) {
					$ip = trim( $ip );
					if ( ! $ips['ipv4'] && filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
						$ips['ipv4'] = $ip;
					}
					if ( ! $ips['ipv6'] && filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6 ) ) {
						$ips['ipv6'] = $ip;
					}
				}
			}

			return $ips;
		}

		/**
		 * Check whether a post can be used by maintenance mode.
		 *
		 * @param int $post_id Post ID.
		 * @return bool
		 */
		public function is_supported_maintenance_post( $post_id ) {
			$post_type = get_post_type_object( get_post_type( $post_id ) );
			return $post_type && $post_type->public;
		}

		/**
		 * Add a maintenance exclusion toggle to post row actions.
		 *
		 * @param array   $actions Existing row actions.
		 * @param WP_Post $post    Current post.
		 * @return array
		 */
		public function add_post_exclusion_row_action( $actions, $post ) {
			if ( ! $this->is_supported_maintenance_post( $post->ID ) || ! current_user_can( 'edit_post', $post->ID ) ) {
				return $actions;
			}

			$is_excluded = (bool) get_post_meta( $post->ID, '_wploti_exclude_from_maintenance', true );
			$actions['wploti-exclude-maintenance'] = sprintf(
				'<a href="#" class="wploti-toggle-post-exclusion" data-post-id="%1$d" data-security="%2$s" data-excluded="%3$d">%4$s</a>',
				$post->ID,
				esc_attr( wp_create_nonce( 'wploti_toggle_post_exclusion_' . $post->ID ) ),
				(int) $is_excluded,
				esc_html( $is_excluded ? __( 'Include in maintenance', 'maintenance-coming-soon-redirect-animation' ) : __( 'Exclude from maintenance', 'maintenance-coming-soon-redirect-animation' ) )
			);

			return $actions;
		}

		/**
		 * Toggle a post's maintenance-mode exclusion.
		 *
		 * @return void
		 */
		public function toggle_post_exclusion() {
			$post_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;
			if ( ! $post_id || ! $this->is_supported_maintenance_post( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
				wp_send_json_error();
			}

			check_ajax_referer( 'wploti_toggle_post_exclusion_' . $post_id, 'security' );
			$is_excluded = (bool) get_post_meta( $post_id, '_wploti_exclude_from_maintenance', true );

			if ( $is_excluded ) {
				delete_post_meta( $post_id, '_wploti_exclude_from_maintenance' );
				$is_excluded = false;
			} else {
				update_post_meta( $post_id, '_wploti_exclude_from_maintenance', '1' );
				$is_excluded = true;
			}

			wp_send_json_success(
				array(
					'excluded' => $is_excluded,
					'label'    => $is_excluded ? __( 'Include in maintenance', 'maintenance-coming-soon-redirect-animation' ) : __( 'Exclude from maintenance', 'maintenance-coming-soon-redirect-animation' ),
				)
			);
		}

		/**
		 * Add a custom maintenance post toggle to post row actions.
		 *
		 * @param array   $actions Existing row actions.
		 * @param WP_Post $post    Current post.
		 * @return array
		 */
		public function add_post_maintenance_row_action( $actions, $post ) {
			if ( ! $this->is_supported_maintenance_post( $post->ID ) || 'publish' !== $post->post_status || ! current_user_can( 'manage_options' ) ) {
				return $actions;
			}

			$is_maintenance_page = absint( get_option( 'wploti_maintenance_page_id', 0 ) ) === (int) $post->ID;
			$actions['wploti-maintenance-page'] = sprintf(
				'<a href="#" class="wploti-toggle-maintenance-post" data-post-id="%1$d" data-security="%2$s">%3$s</a>',
				$post->ID,
				esc_attr( wp_create_nonce( 'wploti_toggle_maintenance_post_' . $post->ID ) ),
				esc_html( $is_maintenance_page ? __( 'Use animation instead', 'maintenance-coming-soon-redirect-animation' ) : __( 'Make maintenance page', 'maintenance-coming-soon-redirect-animation' ) )
			);

			return $actions;
		}

		/**
		 * Select or clear the custom maintenance post.
		 *
		 * @return void
		 */
		public function toggle_maintenance_post() {
			$post_id = isset( $_POST['post_id'] ) ? absint( wp_unslash( $_POST['post_id'] ) ) : 0;
			if ( ! $post_id || ! $this->is_supported_maintenance_post( $post_id ) || 'publish' !== get_post_status( $post_id ) || ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error();
			}

			check_ajax_referer( 'wploti_toggle_maintenance_post_' . $post_id, 'security' );
			$current_page_id = absint( get_option( 'wploti_maintenance_page_id', 0 ) );
			$maintenance_page_id = $current_page_id === $post_id ? 0 : $post_id;
			update_option( 'wploti_maintenance_page_id', $maintenance_page_id );

			wp_send_json_success(
				array(
					'is_maintenance_page' => (bool) $maintenance_page_id,
					'label'               => $maintenance_page_id ? __( 'Use animation instead', 'maintenance-coming-soon-redirect-animation' ) : __( 'Make maintenance page', 'maintenance-coming-soon-redirect-animation' ),
				)
			);
		}

		/**
		 * Register row actions for all public post types.
		 *
		 * @return void
		 */
		public function register_post_type_row_actions() {
			foreach ( get_post_types( array( 'public' => true ), 'names' ) as $post_type ) {
				add_filter( $post_type . '_row_actions', array( $this, 'add_post_exclusion_row_action' ), 10, 2 );
				add_filter( $post_type . '_row_actions', array( $this, 'add_post_maintenance_row_action' ), 11, 2 );
			}
		}
		
		/**
		 * (php) determine user class c
		 *
		 * @since 1.0.0
		 * @access public
		 * @return string
		 */

		public function get_user_class_c(){
			$ip = $this->get_user_ip();
			if ( ! filter_var( $ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 ) ) {
				return '';
			}
			$ip_parts = explode( '.', $ip );
			$class_c = $ip_parts[0] . '.' . $ip_parts[1] . '.' .$ip_parts[2] . '.*';
			return $class_c;
		}


		/**
		 * Reset settings 
		 *
		 * @since 1.0.0
		 * @throws Exception
		 */

		public function reset_plugin_settings() {

			global $wpdb , $wploti_message;

			try {
				// check capabilities
				if ( ! current_user_can('manage_options') ) {
					throw new Exception( esc_html__( 'You do not have access to this resource.', 'maintenance-coming-soon-redirect-animation' ) );
				}

				// check nonce
				check_ajax_referer( 'wploti_nonce', 'security' );

				// update options using the default values

				$wploti_whitelisted_roles = array('administrator');
				$wploti_whitelisted_users = array();
				$wploti_message = esc_html__( 'This site is currently undergoing maintenance. Please check back later', 'maintenance-coming-soon-redirect-animation' );


				update_option('wploti_animation', wploti_animation_dir . 'default-animation.json');
				update_option('wploti_header_type', '200');
				update_option('wploti_message', $wploti_message);
				update_option('wploti_whitelisted_roles', $wploti_whitelisted_roles);						
				update_option('wploti_whitelisted_users', $wploti_whitelisted_users);
				update_option( 'wploti_maintenance_page_id', 0 );
				update_option( 'wploti_custom_login_enabled', '0' );
				update_option( 'wploti_custom_login_slug', '' );
				update_option( 'wploti_private_login_enabled', '1' );
				delete_option( 'wploti_access_password' );
				delete_option( 'wploti_password_enabled' );

				// delete ip_adresses & access keys
				$ip_adresses = $wpdb->prefix . $this->admin_options_name . '_unrestricted_ips';
				$access_keys = $wpdb->prefix . $this->admin_options_name . '_access_keys';
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query( " DELETE FROM `". esc_sql($ip_adresses) ."` " );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->query( " DELETE FROM `". esc_sql($access_keys) ."` " );
				
				wp_send_json_success();

			} catch ( Exception $ex ) {
				wp_send_json_error( $ex->getMessage() );
			}
		}

		/**
		 * (php) generate key
		 *
		 * @since 1.0.0
		 * @access public
		 * @return string
		 */					

		public function alphastring( $len = 20, $valid_chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789' ){
			$str  = '';
			$chrs = explode( ' ', $valid_chars );
			for( $i=0; $i<$len; $i++ ){
				$str .= $valid_chars[ wp_rand( 1, strlen( $valid_chars ) - 1 ) ];
			}
			return $str;
		}
		

		/**
		 * (php)  add site favicon on admin panel
		 *
		 * @since 2.0.0
		 * @access public
		 * @return void
		 */

		public function add_site_favicon() {

			$favicon_link = '<link rel="icon" type="image/x-icon" href="'.  plugin_dir_url( __FILE__ ).'/images/alert-icon.png' .'">';

			echo wp_kses( $favicon_link,
				array(
					'link' => array(
						'href' => array(),
						'type' => array(),
						'rel' => array(),
					)
				)
			);
		}

		/**
		 * (php)  generate maintenance page
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */

		public function generate_maintenance_page() {
			// Enqueue styles and scripts
			wp_enqueue_style(
				'wploti-front-style',
				plugin_dir_url(__FILE__) . 'css/front-style.css',
				array(),
				WPLOTI_VERSION
			);
		
			wp_enqueue_script(
				'wploti-lottie-player',
				plugin_dir_url(__FILE__) . 'js/lottie-player-script.js',
				array(),
				WPLOTI_VERSION,
				true
			);
			wp_enqueue_script( 'wploti-front-panel', plugin_dir_url( __FILE__ ) . 'js/front-panel.js', array( 'jquery' ), WPLOTI_VERSION, true );

			if (!empty(get_option('wploti_message')) && isset($this->console_style)) {
				$inline_script = sprintf(
					'console.warn("%%c %s", "%s");',
					esc_js(wp_strip_all_tags(get_option('wploti_message'))),
					esc_js($this->console_style)
				);

				wp_add_inline_script('wploti-lottie-player', $inline_script);
			}
		
			// wp_enqueue_emoji_styles() requires WP 6.4+; our minimum supported version is 5.0.
			if ( function_exists( 'wp_enqueue_emoji_styles' ) ) {
				wp_enqueue_emoji_styles();
			}
		
			// Output the maintenance page head
			$maintenance_head = sprintf(
				'<title>%s</title>
				<meta charset="utf-8">
				<meta http-equiv="X-UA-Compatible" content="IE=edge">
				<link rel="icon" type="image/x-icon" href="%s">
				<meta name="viewport" content="width=device-width, initial-scale=1">',
				esc_html(get_bloginfo('name')),
				esc_url(plugin_dir_url(__FILE__) . 'images/alert-icon.png')
			);
		
			echo wp_kses($maintenance_head, array(
				'title' => array(),
				'meta' => array(
					'name' => array(),
					'content' => array(),
					'charset' => array(),
					'http-equiv' => array(),
				),
				'link' => array(
					'href' => array(),
					'type' => array(),
					'rel' => array(),
				),
			));
		
			// Output the maintenance page body
			$maintenance_html = sprintf(
				'<!DOCTYPE html><html><body class="wploti-maintenance-animation">
				<lottie-player autoplay="true" loop src="%s"></lottie-player>',
				esc_url(get_option('wploti_animation', 'default-animation.json'))
			);
		
			echo wp_kses($maintenance_html, array(
				'html' => array(),
				'body' => array(
					'class' => array(),
				),
				'lottie-player' => array(
					'autoplay' => array(),
					'loop' => array(),
					'src' => array(),
				),
			));
		
			echo wp_kses_post(get_option('wploti_message'));
			$this->render_access_panels();
			echo '</body></html>';
		
			// Print enqueued styles and scripts
			wp_print_head_scripts();
			wp_print_styles();
			wp_print_footer_scripts();
		
			exit();
		}

		/**
		 * Redirect to the selected Page so WordPress loads its normal template,
		 * styles, and scripts.
		 *
		 * @param int $page_id Page ID.
		 * @return void
		 */
		public function generate_custom_maintenance_page( $page_id ) {
			$page = get_post( $page_id );
			if ( ! $page || 'publish' !== $page->post_status ) {
				$this->generate_maintenance_page();
			}

			$maintenance_page_url = add_query_arg( 'wploti_mr_maintenance_page', '1', get_permalink( $page_id ) );
			wp_safe_redirect( $maintenance_page_url );
			exit();
		}

		public function render_access_panels() {
			$password_enabled = '1' === get_option( 'wploti_password_enabled', '0' ) && $this->get_access_password();
			$private_login_enabled = '1' === get_option( 'wploti_private_login_enabled', '1' );
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- read-only flag used to display an error message, not to process data.
			$password_error = isset( $_POST['wploti_mr_password'] ) ? '<p class="wploti-panel-error">' . esc_html__( 'That password is not correct.', 'maintenance-coming-soon-redirect-animation' ) . '</p>' : '';
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- read-only flag used to display an error message, not to process data.
			$login_error = isset( $_POST['wploti_mr_private_login'] ) ? '<p class="wploti-panel-error">' . esc_html__( 'The login details are not valid.', 'maintenance-coming-soon-redirect-animation' ) . '</p>' : '';
			if ( ! $password_enabled && ! $private_login_enabled ) {
				return;
			}
			?>
			<div class="wploti-access-controls">
				<?php if ( $password_enabled ) : ?><button type="button" class="wploti-access-trigger" data-wploti-panel="public" aria-label="<?php esc_attr_e( 'Open password access', 'maintenance-coming-soon-redirect-animation' ); ?>">&#128273;</button><?php endif; ?>
				<?php if ( $private_login_enabled ) : ?><button type="button" class="wploti-access-trigger" data-wploti-panel="private" aria-label="<?php esc_attr_e( 'Open private login', 'maintenance-coming-soon-redirect-animation' ); ?>">&#128274;</button><?php endif; ?>
			</div>
			<?php if ( $password_enabled ) : ?><aside class="wploti-access-panel" data-wploti-panel="public" aria-hidden="true"><button type="button" class="wploti-panel-close" aria-label="<?php esc_attr_e( 'Close', 'maintenance-coming-soon-redirect-animation' ); ?>">&times;</button><h2><?php esc_html_e( 'Password Access', 'maintenance-coming-soon-redirect-animation' ); ?></h2><form method="post"><label for="wploti-mr-password"><?php esc_html_e( 'Password', 'maintenance-coming-soon-redirect-animation' ); ?></label><input id="wploti-mr-password" name="wploti_mr_password" type="password" required autocomplete="current-password"><?php wp_nonce_field( 'wploti_mr_password_action', 'wploti_mr_password_nonce' ); ?><button type="submit"><?php esc_html_e( 'Open Website', 'maintenance-coming-soon-redirect-animation' ); ?></button><?php echo wp_kses_post( $password_error ); ?></form></aside><?php endif; ?>
			<?php if ( $private_login_enabled ) : ?><aside class="wploti-access-panel" data-wploti-panel="private" aria-hidden="true"><button type="button" class="wploti-panel-close" aria-label="<?php esc_attr_e( 'Close', 'maintenance-coming-soon-redirect-animation' ); ?>">&times;</button><h2><?php esc_html_e( 'Private Login', 'maintenance-coming-soon-redirect-animation' ); ?></h2><form method="post"><label for="wploti-private-username"><?php esc_html_e( 'Username or Email', 'maintenance-coming-soon-redirect-animation' ); ?></label><input id="wploti-private-username" name="log" type="text" required autocomplete="username"><label for="wploti-private-password"><?php esc_html_e( 'Password', 'maintenance-coming-soon-redirect-animation' ); ?></label><input id="wploti-private-password" name="pwd" type="password" required autocomplete="current-password"><input name="wploti_mr_private_login" type="hidden" value="1"><?php wp_nonce_field( 'wploti_mr_private_login_action', 'wploti_mr_login_nonce' ); ?><button type="submit"><?php esc_html_e( 'Log In', 'maintenance-coming-soon-redirect-animation' ); ?></button><?php echo wp_kses_post( $login_error ); ?></form></aside><?php endif; ?>
			<?php
		}

		public function add_maintenance_page_body_class( $classes ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only flag, only used to add a body class.
			if ( isset( $_GET['wploti_mr_maintenance_page'] ) && is_singular() ) {
				$classes[] = 'wploti-maintenance-page-view';
			}
			return $classes;
		}

		public function render_custom_maintenance_access_panels() {
			$maintenance_page_id = absint( get_option( 'wploti_maintenance_page_id', 0 ) );
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only flag, only used to decide whether to render the access panels.
			if ( isset( $_GET['wploti_mr_maintenance_page'] ) && is_singular() && get_queried_object_id() === $maintenance_page_id ) {
				$this->render_access_panels();
				echo '<style>
					body.wploti-maintenance-page-view header,
					body.wploti-maintenance-page-view footer,
					body.wploti-maintenance-page-view .site-header,
					body.wploti-maintenance-page-view .site-footer,
					body.wploti-maintenance-page-view .header,
					body.wploti-maintenance-page-view .footer,
					body.wploti-maintenance-page-view nav,
					body.wploti-maintenance-page-view .main-navigation {
						display: none !important;
					}
				</style>';
				echo '<script>
					(function() {
						var hideSelectors = [
							"header",
							"footer",
							".site-header",
							".site-footer",
							".header",
							".footer",
							"nav",
							".main-navigation"
						];
						function hideMaintenanceChrome() {
							hideSelectors.forEach(function(selector) {
								document.querySelectorAll(selector).forEach(function(el) {
									el.style.display = "none";
								});
							});
						}
						document.addEventListener("DOMContentLoaded", hideMaintenanceChrome);
						window.addEventListener("load", hideMaintenanceChrome);
						setTimeout(hideMaintenanceChrome, 200);
					})();
				</script>';
			}
		}

		
		/**
		 * (php)  #####  main function  ##### 
		 * 		#####  Redirection process  #####
		 * 		find out if we need to redirect or not
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */			

		public function process_redirect() {
			global $wpdb;
			
			$valid_ips      = array();
			$valid_class_cs = array();
			$valid_aks      = array();
			$wploti_matches  = apply_filters( 'wploti_matches', array() );
			$current_user = wp_get_current_user();
			$maintenance_page_id = absint( get_option( 'wploti_maintenance_page_id', 0 ) );
			// Only the configured custom maintenance page may use this internal marker.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET['wploti_mr_maintenance_page'] ) && is_singular() && get_queried_object_id() === $maintenance_page_id ) {
				$wploti_matches[] = "<!-- wploti_MR: CUSTOM MAINTENANCE PAGE -->";
			}
			
			// Get all valid access keys
			$table_name = $wpdb->prefix . $this->admin_options_name . "_access_keys";
			$sql = $wpdb->prepare(
				"SELECT access_key FROM " . esc_sql($table_name) . " WHERE active = %d",
				1
			);
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$aks = $wpdb->get_results($sql, OBJECT);
			if ($aks) {
				foreach ($aks as $ak) {
					$valid_aks[] = $ak->access_key;
				}
			}

			$password_enabled = '1' === get_option( 'wploti_password_enabled', '0' );
			$access_password = $this->get_access_password();
			$password_nonce_valid = isset( $_POST['wploti_mr_password_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wploti_mr_password_nonce'] ) ), 'wploti_mr_password_action' );
			if ( $password_enabled && $access_password && $password_nonce_valid && isset( $_POST['wploti_mr_password'] ) ) {
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- password value must remain unchanged for the hash comparison below.
				$password = wp_unslash( $_POST['wploti_mr_password'] );
				if ( hash_equals( $access_password, $password ) ) {
					$this->set_password_cookie();
					wp_safe_redirect( home_url( '/' ) );
					exit;
				}
			}

			$private_login_enabled = '1' === get_option( 'wploti_private_login_enabled', '1' );
			$login_nonce_valid = isset( $_POST['wploti_mr_login_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['wploti_mr_login_nonce'] ) ), 'wploti_mr_private_login_action' );
			if ( $private_login_enabled && $login_nonce_valid && isset( $_POST['wploti_mr_private_login'] ) ) {
				$credentials = array(
					'user_login'    => isset( $_POST['log'] ) ? sanitize_user( wp_unslash( $_POST['log'] ) ) : '',
					// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- password value must remain unchanged for authentication.
					'user_password' => isset( $_POST['pwd'] ) ? wp_unslash( $_POST['pwd'] ) : '',
					'remember'      => true,
				);
				$user = wp_signon( $credentials, is_ssl() );
				if ( ! is_wp_error( $user ) && user_can( $user, 'read' ) ) {
					wp_safe_redirect( admin_url() );
					exit;
				}
				if ( ! is_wp_error( $user ) ) {
					wp_logout();
				}
			}
			
			// Handle access key from URL
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( isset( $_GET['wploti_mr_temp_access_key'] ) && trim( $_GET['wploti_mr_temp_access_key'] ) != '' ) {
				// Properly sanitize the access key without unslashing as this will break the access
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.NonceVerification.Recommended
				$access_key = sanitize_text_field( wp_unslash( $_GET['wploti_mr_temp_access_key'] ) );
				
				// Check if the access key is valid
				if( in_array( $access_key, $valid_aks ) ){
					// Use a persistent cookie rather than a native PHP session: sessions
					// rely on server-side file storage that isn't reliable across
					// load-balanced hosting/page-caching setups and frequently caused
					// the access key to silently fail to bypass maintenance mode.
					$this->set_access_key_cookie( $access_key );
					
					// Redirect to clean URL
					$redirect_url = remove_query_arg( 'wploti_mr_temp_access_key' );
					wp_safe_redirect( $redirect_url );
					exit;
				}
			}
			
			// Check existing access key cookie
			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.NonceVerification.Recommended, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if( isset( $_COOKIE['wploti_mr_access_key'] ) && $_COOKIE['wploti_mr_access_key'] != '' ){
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.NonceVerification.Recommended
				$cookie_key = sanitize_text_field( wp_unslash( $_COOKIE['wploti_mr_access_key'] ) );
				
				// Check if key is still valid
				if( in_array( $cookie_key, $valid_aks ) ){
					$wploti_matches[] = "<!-- wploti_MR: ACCESS KEY MATCH -->";
				} else {
					// Key is no longer valid, clear the cookie
					$this->clear_access_key_cookie();
				}
			}

			if ( $password_enabled && $access_password && isset( $_COOKIE['wploti_mr_password'] ) && hash_equals( $this->get_password_cookie_value( $access_password ), (string) wp_unslash( $_COOKIE['wploti_mr_password'] ) ) ) {
				$wploti_matches[] = "<!-- wploti_MR: PASSWORD MATCH -->";
			}
			
			// skip admin pages by default
			$request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
			$url_parts = explode( '/', sanitize_text_field($request_uri) );
			
			if( in_array( 'wp-admin', $url_parts ) ) {
				$wploti_matches[] = "<!-- wploti_MR: SKIPPING ADMIN -->";
			} else {
				// determine if user is admin.. if so, bypass all of this.
				if ( current_user_can( apply_filters( 'wploti_user_can', 'manage_options' ) ) ) {
					$wploti_matches[] = "<!-- wploti_MR: USER IS ADMIN -->";
				} else {
					if( $this->wploti_active() == '1' ) {
						if ( is_singular() && get_post_meta( get_queried_object_id(), '_wploti_exclude_from_maintenance', true ) ) {
							$wploti_matches[] = "<!-- wploti_MR: PAGE EXCLUSION MATCH -->";
						}
						// get valid unrestricted IPs
						$table_name = $wpdb->prefix . $this->admin_options_name . "_unrestricted_ips";
						$sql = $wpdb->prepare(
							"SELECT ip_address FROM " . esc_sql($table_name) . " WHERE active = %d",
							1
						);
						// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
						$ips = $wpdb->get_results($sql, OBJECT);

						if ($ips) {
							foreach ($ips as $ip) {
								$ip_parts = explode('.', $ip->ip_address);
								if ( count( $ip_parts ) === 4 && $ip_parts[3] === '*' ) {
									$valid_class_cs[] = $ip_parts[0] . '.' . $ip_parts[1] . '.' . $ip_parts[2];
								} elseif ( filter_var( $ip->ip_address, FILTER_VALIDATE_IP ) ) {
									$valid_ips[] = $ip->ip_address;
								}
							}
						}
						
						// manage ip filtering 
						$user_ip = $this->get_user_ip();
						$user_ip_binary = inet_pton( $user_ip );
						$has_exact_ip_match = false;
						foreach ( $valid_ips as $valid_ip ) {
							if ( $user_ip_binary !== false && $user_ip_binary === inet_pton( $valid_ip ) ) {
								$has_exact_ip_match = true;
								break;
							}
						}
						if( $has_exact_ip_match ) {
							$wploti_matches[] = "<!-- wploti_MR: IP MATCH -->";
						} else {
							// check for partial ( class c ) match
							$ip_parts     = explode( '.', $this->get_user_ip() );
							if(count($ip_parts) >= 3) {
								$user_class_c = $ip_parts[0] . '.' . $ip_parts[1] . '.' . $ip_parts[2];
								if( in_array( $user_class_c, $valid_class_cs ) ) {
									$wploti_matches[] = "<!-- wploti_MR: CLASS C MATCH -->";
								}
							}
						}

						// skip Whitelisted User Roles
						$whitelisted_roles = get_option('wploti_whitelisted_roles');
						if( is_array($whitelisted_roles) && $this->user_has_role( $whitelisted_roles ) ) {
							$wploti_matches[] = "<!-- wploti_MR: ROLE MATCH -->";
						}

						// skip Whitelisted Users
						$whitelisted_users = get_option('wploti_whitelisted_users');
						if( is_array($whitelisted_users) && in_array($current_user->ID, $whitelisted_users) ) {
							$wploti_matches[] = "<!-- wploti_MR: USER MATCH -->";
						}
						
						if( count( $wploti_matches ) == 0 ) {
							if ( $maintenance_page_id ) {
								$this->generate_custom_maintenance_page( $maintenance_page_id );
							}

							// No custom page selected. Show the animation maintenance page.
							$this->generate_maintenance_page();
						}
					} else {
						$wploti_matches[] = "<!-- wploti_MR: REDIR DISABLED -->";
					}
				}
			}
		}

		/**
		 * (php) set the temporary access key cookie
		 *
		 * @since 2.3.4
		 * @access private
		 * @param string $access_key Access key value.
		 * @return void
		 */
		private function set_access_key_cookie( $access_key ) {
			setcookie(
				'wploti_mr_access_key',
				$access_key,
				array(
					'expires'  => time() + ( 24 * HOUR_IN_SECONDS ),
					'path'     => defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/',
					'domain'   => defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '',
					'secure'   => is_ssl(),
					'httponly' => true,
					'samesite' => 'Lax',
				)
			);
			$_COOKIE['wploti_mr_access_key'] = $access_key;
		}

		/**
		 * (php) clear the temporary access key cookie
		 *
		 * @since 2.3.4
		 * @access private
		 * @return void
		 */
		private function clear_access_key_cookie() {
			setcookie(
				'wploti_mr_access_key',
				'',
				array(
					'expires'  => time() - HOUR_IN_SECONDS,
					'path'     => defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/',
					'domain'   => defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '',
					'secure'   => is_ssl(),
					'httponly' => true,
					'samesite' => 'Lax',
				)
			);
			unset( $_COOKIE['wploti_mr_access_key'] );
		}

		private function set_password_cookie() {
			$access_password = $this->get_access_password();
			setcookie( 'wploti_mr_password', $this->get_password_cookie_value( $access_password ), array(
				'expires'  => time() + ( 24 * HOUR_IN_SECONDS ),
				'path'     => defined( 'COOKIEPATH' ) && COOKIEPATH ? COOKIEPATH : '/',
				'domain'   => defined( 'COOKIE_DOMAIN' ) ? COOKIE_DOMAIN : '',
				'secure'   => is_ssl(),
				'httponly' => true,
				'samesite' => 'Lax',
			) );
		}

		private function get_password_cookie_value( $access_password ) {
			return hash_hmac( 'sha256', $access_password, wp_salt( 'auth' ) );
		}

		private function get_access_password() {
			$encrypted_password = get_option( 'wploti_access_password', '' );
			if ( ! is_string( $encrypted_password ) || 0 !== strpos( $encrypted_password, 'enc:' ) ) {
				return '';
			}

			$payload = base64_decode( substr( $encrypted_password, 4 ), true );
			if ( false === $payload || strlen( $payload ) <= 16 ) {
				return '';
			}

			$encryption_key = hash( 'sha256', wp_salt( 'auth' ), true );
			return (string) openssl_decrypt( substr( $payload, 16 ), 'aes-256-cbc', $encryption_key, OPENSSL_RAW_DATA, substr( $payload, 0, 16 ) );
		}

		private function encrypt_access_password( $access_password ) {
			$encryption_key = hash( 'sha256', wp_salt( 'auth' ), true );
			$iv = random_bytes( 16 );
			$ciphertext = openssl_encrypt( $access_password, 'aes-256-cbc', $encryption_key, OPENSSL_RAW_DATA, $iv );
			return false === $ciphertext ? '' : 'enc:' . base64_encode( $iv . $ciphertext );
		}

		/**
		 * (php)  add new IP
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */			
		
		public function add_new_ip() {
			if ( !current_user_can('manage_options') ) wp_die("Oh no you don't!");
			check_ajax_referer( 'wploti_nonce', 'security' );
			global $wpdb;
			$this->upgrade_unrestricted_ips_table();
			$tbl = $wpdb->prefix . $this->admin_options_name . '_unrestricted_ips';
			$name = isset($_POST['wploti_mr_ip_name']) ? (sanitize_text_field(wp_unslash($_POST['wploti_mr_ip_name']))) : '';
			$ip_address = isset($_POST['wploti_mr_ip_ip']) ? trim(sanitize_text_field(wp_unslash($_POST['wploti_mr_ip_ip']))) : '';
			$is_ipv4_wildcard = (bool) preg_match( '/^(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.\*$/', $ip_address );

			if ( ! $is_ipv4_wildcard && ! filter_var( $ip_address, FILTER_VALIDATE_IP ) ) {
				esc_html_e( 'IP address not valid', 'maintenance-coming-soon-redirect-animation' );
				wp_die();
			}

			$sql = $wpdb->prepare(
				"INSERT INTO `" . esc_sql($tbl) . "` 
				(name, ip_address, created_at) 
				VALUES (%s, %s, NOW())",
				$name,
				$ip_address
			);
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rs = $wpdb->query($sql);
			if( $rs ){
				// send table data
				$this->print_unrestricted_ips();
			}else{
				esc_html_e( 'Unable to add IP because of a database error. Please reload the page.' , 'maintenance-coming-soon-redirect-animation' );
			}
			die();
		}

		/**
		 * (php)  toggle IP status
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */
				
		public function toggle_ip_status(){
			if ( !current_user_can('manage_options') ) wp_die("Oh no you don't!");
			check_ajax_referer( 'wploti_nonce', 'security' );
			global $wpdb;
			$tbl = $wpdb->prefix . $this->admin_options_name . '_unrestricted_ips';
			$ip_id = isset($_POST['wploti_mr_ip_id']) ? sanitize_text_field(wp_unslash($_POST['wploti_mr_ip_id'])) : '';
			$ip_active = isset($_POST['wploti_mr_ip_active']) ? 
				((sanitize_text_field(wp_unslash($_POST['wploti_mr_ip_active'])) == 1) ? 1 : 0) : 0;

			$sql = $wpdb->prepare(
				"UPDATE `" . esc_sql($tbl) . "` 
				SET active = %d 
				WHERE id = %d",
				$ip_active,
				$ip_id
			);
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rs = $wpdb->query($sql);
			if( $rs ){				
				echo 'SUCCESS' . '|' . esc_html($ip_id) . '|' . esc_html($ip_active);
			}else{			
				echo 'ERROR';
			}
			die();
		}

		/**
		 * Update an unrestricted IP record.
		 *
		 * @return void
		 */
		public function update_ip() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( "Oh no you don't!" );
			}
			check_ajax_referer( 'wploti_nonce', 'security' );

			global $wpdb;
			$tbl = $wpdb->prefix . $this->admin_options_name . '_unrestricted_ips';
			$ip_id = isset( $_POST['wploti_mr_ip_id'] ) ? absint( wp_unslash( $_POST['wploti_mr_ip_id'] ) ) : 0;
			$name = isset( $_POST['wploti_mr_ip_name'] ) ? sanitize_text_field( wp_unslash( $_POST['wploti_mr_ip_name'] ) ) : '';
			$ip_address = isset( $_POST['wploti_mr_ip_ip'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['wploti_mr_ip_ip'] ) ) ) : '';
			$is_ipv4_wildcard = (bool) preg_match( '/^(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.\*$/', $ip_address );

			if ( ! $ip_id || '' === $name || ( ! $is_ipv4_wildcard && ! filter_var( $ip_address, FILTER_VALIDATE_IP ) ) ) {
				esc_html_e( 'IP address not valid', 'maintenance-coming-soon-redirect-animation' );
				wp_die();
			}

			$sql = $wpdb->prepare(
				'UPDATE `' . esc_sql( $tbl ) . '` SET name = %s, ip_address = %s WHERE id = %d',
				$name,
				$ip_address,
				$ip_id
			);
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rs = $wpdb->query( $sql );

			if ( false !== $rs ) {
				$this->print_unrestricted_ips();
			} else {
				esc_html_e( 'Unable to update IP because of a database error. Please reload the page.', 'maintenance-coming-soon-redirect-animation' );
			}
			wp_die();
		}
		
		/**
		 * (php)  delete IP
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */
		
		public function delete_ip(){
			if ( !current_user_can('manage_options') ) wp_die("Oh no you don't!");
			check_ajax_referer( 'wploti_nonce', 'security' );
			global $wpdb;
			$tbl = $wpdb->prefix . $this->admin_options_name . '_unrestricted_ips';
			$ip_id = isset($_POST['wploti_mr_ip_id']) ? sanitize_text_field(wp_unslash($_POST['wploti_mr_ip_id'])) : '';

			$sql = $wpdb->prepare(
				"DELETE FROM `" . esc_sql($tbl) . "` 
				WHERE id = %d",
				$ip_id
			);
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rs = $wpdb->query($sql);
			if( $rs ){
				$this->print_unrestricted_ips();
			}else{
				esc_html_e( 'Unable to delete IP because of a database error. Please reload the page.' , 'maintenance-coming-soon-redirect-animation' );
			}
			die();
		}
		
		/**
		 * (php)  add new Access Key
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */

		private function get_access_key_email( $access_key ) {
			$site_name = get_bloginfo( 'name' );
			$access_url = add_query_arg( 'wploti_mr_temp_access_key', rawurlencode( $access_key ), home_url( '/' ) );
			$site_name_html = esc_html( $site_name );
			$access_url_html = esc_html( $access_url );

			return '<!doctype html><html><body style="margin:0;padding:0;background:#f4f7fb;color:#263238;font-family:Arial,sans-serif;">'
				. '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f4f7fb;padding:32px 12px;"><tr><td align="center">'
				. '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;background:#ffffff;border:1px solid #dfe6ee;border-radius:8px;overflow:hidden;">'
				. '<tr><td style="background:#1d4ed8;padding:28px 32px;color:#ffffff;"><div style="font-size:13px;letter-spacing:1px;text-transform:uppercase;opacity:.85;">Temporary access</div><h1 style="margin:8px 0 0;font-size:25px;line-height:1.25;font-weight:700;">Access link for ' . $site_name_html . '</h1></td></tr>'
				. '<tr><td style="padding:32px;"><p style="margin:0 0 16px;font-size:16px;line-height:1.6;">You have been granted temporary access to <strong>' . $site_name_html . '</strong>.</p>'
				. '<p style="margin:0 0 24px;font-size:15px;line-height:1.6;color:#52606d;">Use the button below to open the website. Cookies must be enabled in your browser for the access link to work.</p>'
				. '<p style="margin:0 0 28px;text-align:center;"><a href="' . esc_url( $access_url ) . '" style="display:inline-block;background:#1d4ed8;border-radius:5px;color:#ffffff;font-size:16px;font-weight:700;padding:14px 24px;text-decoration:none;">Open website</a></p>'
				. '<p style="margin:0 0 8px;font-size:13px;font-weight:700;color:#52606d;">Or copy this link into your browser:</p><p style="margin:0 0 24px;word-break:break-all;font-size:13px;line-height:1.5;"><a href="' . esc_url( $access_url ) . '" style="color:#1d4ed8;">' . $access_url_html . '</a></p>'
				. '<p style="margin:0;padding-top:18px;border-top:1px solid #e7edf3;font-size:13px;line-height:1.6;color:#718096;">This access link is valid for 24 hours. Please do not share it with others.</p></td></tr>'
				. '</table><p style="margin:18px 0 0;font-size:12px;color:#8795a1;">' . $site_name_html . '</p></td></tr></table></body></html>';
		}

		public function add_new_ak() {
			if ( !current_user_can('manage_options') ) wp_die("Oh no you don't!");
			check_ajax_referer( 'wploti_nonce', 'security' );
			global $wpdb;
			$tbl = $wpdb->prefix . $this->admin_options_name . '_access_keys';
			$name = isset($_POST['wploti_mr_ak_name']) ? sanitize_text_field(wp_unslash($_POST['wploti_mr_ak_name'])) : '';
			$email = isset($_POST['wploti_mr_ak_email']) ? sanitize_email(wp_unslash($_POST['wploti_mr_ak_email'])) : '';
			$access_key = sanitize_text_field($this->alphastring(20));

			$sql = $wpdb->prepare(
				"INSERT INTO `" . esc_sql($tbl) . "` 
				(name, email, access_key, created_at) 
				VALUES (%s, %s, %s, NOW())",
				$name,
				$email,
				$access_key
			);
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rs = $wpdb->query($sql);
			if( $rs ){
				// email user
				$subject    = sprintf( /* translators: %s = name of the website/blog */ __( "Access Key Link for %s" , "maintenance-coming-soon-redirect-animation" ), get_bloginfo() );
				$full_msg  = $this->get_access_key_email( $access_key );
				$headers    = array( 'Content-Type: text/html; charset=UTF-8' );
				$mail_sent  = wp_mail( $email, $subject, $full_msg, $headers );
				echo ( esc_html($mail_sent) ) ? '<!-- SEND_SUCCESS -->' : '<!-- SEND_FAILURE -->';
				// send table data
				$this->print_access_keys();
			}else{
				esc_html_e( "Unable to add Access Key because of a database error. Please reload the page." , "maintenance-coming-soon-redirect-animation" );
			}
			die();
		}

		public function save_password() {
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( esc_html__( 'You do not have access to this resource.', 'maintenance-coming-soon-redirect-animation' ) );
			}
			check_ajax_referer( 'wploti_nonce', 'security' );

			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- password value must remain unchanged so special characters remain valid.
			$password = isset( $_POST['wploti_access_password'] ) ? wp_unslash( $_POST['wploti_access_password'] ) : '';
			$enabled = isset( $_POST['wploti_password_enabled'] ) && '1' === sanitize_text_field( wp_unslash( $_POST['wploti_password_enabled'] ) ) ? '1' : '0';
			if ( $password ) {
				update_option( 'wploti_access_password', $this->encrypt_access_password( $password ) );
			}
			update_option( 'wploti_password_enabled', $enabled );
			echo 'SUCCESS';
			wp_die();
		}
		
		/**
		 * (php)  toggle Access Key status
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */
		
		public function toggle_ak_status(){
			if ( !current_user_can('manage_options') ) wp_die("Oh no you don't!");
			check_ajax_referer( 'wploti_nonce', 'security' );
			global $wpdb;
			$tbl = $wpdb->prefix . $this->admin_options_name . '_access_keys';
			$ak_id = isset($_POST['wploti_mr_ak_id']) ? sanitize_text_field(wp_unslash($_POST['wploti_mr_ak_id'])) : '';
			$ak_active = isset($_POST['wploti_mr_ak_active']) ? 
				((sanitize_text_field(wp_unslash($_POST['wploti_mr_ak_active'])) == 1) ? 1 : 0) : 0;

			$sql = $wpdb->prepare(
				"UPDATE `" . esc_sql($tbl) . "` 
				SET active = %d 
				WHERE id = %d",
				$ak_active,
				$ak_id
			);
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rs = $wpdb->query($sql);
			if( $rs ){				
				echo 'SUCCESS' . '|' . esc_html($ak_id) . '|' . esc_html($ak_active);
			}else{				
				echo 'ERROR';
			}
			die();
		}
		
		/**
		 * (php)  delete Access Key
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */
		
		public function delete_ak(){
			if ( !current_user_can('manage_options') ) wp_die("Oh no you don't!");
			check_ajax_referer( 'wploti_nonce', 'security' );
			global $wpdb;
			$tbl = $wpdb->prefix . $this->admin_options_name . '_access_keys';
			$ak_id = isset($_POST['wploti_mr_ak_id']) ? sanitize_text_field(wp_unslash($_POST['wploti_mr_ak_id'])) : '';

			$sql = $wpdb->prepare(
				"DELETE FROM `" . esc_sql($tbl) . "` 
				WHERE id = %d",
				$ak_id
			);
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$rs = $wpdb->query($sql);
			if( $rs ){
				$this->print_access_keys();
			}else{
				esc_html_e( 'Unable to delete Access Key because of a database error. Please reload the page.' , 'maintenance-coming-soon-redirect-animation');
			}
			die();
		}
		
		/**
		 * (php)  resend Access Key email
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */
		
		public function resend_ak(){
			if ( !current_user_can('manage_options') ) wp_die("Oh no you don't!");
			check_ajax_referer( 'wploti_nonce', 'security' );
			global $wpdb;
			$tbl = $wpdb->prefix . $this->admin_options_name . '_access_keys';
			$ak_id = isset($_POST['wploti_mr_ak_id']) ? sanitize_text_field(wp_unslash($_POST['wploti_mr_ak_id'])) : '';
			$sql = $wpdb->prepare( 
				"SELECT * FROM " . esc_sql( $tbl ) . " WHERE id = %d", 
				$ak_id 
			);
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$ak = $wpdb->get_row($sql);
			if( $ak ){
				$subject    = sprintf( /* translators: %s = name of the website/blog */ __( "Access Key Link for %s" , "maintenance-coming-soon-redirect-animation"), get_bloginfo() );
				$full_msg  = $this->get_access_key_email( $ak->access_key );
				$headers    = array( 'Content-Type: text/html; charset=UTF-8' );
				$mail_sent  = wp_mail( $ak->email, $subject, $full_msg, $headers );
				echo ( esc_html($mail_sent) ) ? 'SEND_SUCCESS' : 'SEND_FAILURE';
			}else{
				echo 'ERROR' ;
			}
			die();
		}
		
		/**
		 * (php)  generate IP table data
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */
		 
		public function print_unrestricted_ips(){
			global $wpdb; ?>

			<table class="widefat fixed" cellspacing="0">
				<thead>
					<tr>
						<th class="column-wploti-ip-name"><?php esc_html_e( "Name" , "maintenance-coming-soon-redirect-animation" ); ?></th>
						<th class="column-wploti-ip-ip"><?php esc_html_e( "IP" , "maintenance-coming-soon-redirect-animation"); ?></th>
						<th class="column-wploti-ip-active"><?php esc_html_e( "Creation Date" , "maintenance-coming-soon-redirect-animation"); ?></th>
						<th class="column-wploti-ip-creation-date"><?php esc_html_e( "Active" , "maintenance-coming-soon-redirect-animation"); ?></th>
						<th class="column-wploti-actions"><?php esc_html_e( "Actions" , "maintenance-coming-soon-redirect-animation"); ?></th>
					</tr>
				</thead>

				<tfoot>
					<tr>
						<th class="column-wploti-ip-name"><?php esc_html_e( "Name" , "maintenance-coming-soon-redirect-animation"); ?></th>
						<th class="column-wploti-ip-ip"><?php esc_html_e( "IP" , "maintenance-coming-soon-redirect-animation"); ?></th>
						<th class="column-wploti-ip-creation-date"><?php esc_html_e( "Creation Date" , "maintenance-coming-soon-redirect-animation"); ?></th>
						<th class="column-wploti-ip-active"><?php esc_html_e( "Active" , "maintenance-coming-soon-redirect-animation"); ?></th>						
						<th class="column-wploti-actions"><?php esc_html_e( "Actions" , "maintenance-coming-soon-redirect-animation"); ?></th>
					</tr>
				</tfoot>

				<tbody>
					<?php

					$tbl = esc_sql($wpdb->prefix . $this->admin_options_name . '_unrestricted_ips');
					$sql = "SELECT * FROM $tbl ORDER BY name";
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$ips = $wpdb->get_results($sql, OBJECT);

					$ip_row_class = 'alternate';
					if( $ips ){
						foreach( $ips as $ip ) : 
							$ip_name = sanitize_text_field($ip->name);
							$ip_address = sanitize_text_field($ip->ip_address);
							$ip_creation_date = sanitize_text_field($ip->created_at);
							$ip_id = sanitize_text_field($ip->id);
						?>
							<tr id="wploti-ip-<?php echo esc_attr($ip_id); ?>" valign="middle"  class="<?php echo esc_attr($ip_row_class); ?>">
								<td class="column-wploti-ip-name"><?php echo esc_html($ip_name); ?></td>
								<td class="column-wploti-ip-ip"><?php echo esc_html($ip_address); ?></td>
								<td class="column-wploti-ip-creation-date"><?php echo esc_html($ip_creation_date); ?></td>
								<td class="column-wploti-ip-active" id="wploti_mr_ip_status_<?php echo esc_attr($ip_id); ?>" ><?php echo ( sanitize_text_field($ip->active) == 1) ? '<span class="green">' .  esc_html__( 'Yes' , 'maintenance-coming-soon-redirect-animation' ) .' </span>' : '<span class="red">' . esc_html__( 'No' , 'maintenance-coming-soon-redirect-animation' ) .' </span>' ; ?></td>
								<td class="column-wploti-actions">
									<span class='edit' id="wploti_mr_ip_status_<?php echo esc_attr($ip_id); ?>_action">
										<?php if( $ip->active == 1 ){ ?>
											<a href="#" class="wploti-mr-toggle-ip" data-ip-id="<?php echo esc_attr( $ip_id ); ?>" data-ip-active="0"><?php esc_html_e( "Disable" , "maintenance-coming-soon-redirect-animation" ); ?></a> | 
										<?php }else{ ?>
											<a href="#" class="wploti-mr-toggle-ip" data-ip-id="<?php echo esc_attr( $ip_id ); ?>" data-ip-active="1"><?php esc_html_e( "Enable" , "maintenance-coming-soon-redirect-animation" ); ?></a> | 
										<?php } ?>
									</span>
										<span class='edit'>
											<a href="#" class="wploti-mr-edit-ip" data-ip-id="<?php echo esc_attr( $ip_id ); ?>"><?php esc_html_e( 'Edit', 'maintenance-coming-soon-redirect-animation' ); ?></a> |
										</span>
									<span class='delete'>
											<a class='submitdelete wploti-mr-delete-ip' href="#" data-ip-id="<?php echo esc_attr( $ip_id ); ?>" data-ip-address="<?php echo esc_attr( $ip_address ); ?>"><?php esc_html_e( "Delete" , "maintenance-coming-soon-redirect-animation" ); ?></a>
									</span>
								</td>
							</tr>
							<?php
							$ip_row_class = ( $ip_row_class == '' ) ? 'alternate' : '';
						endforeach;
					}
					?>
					
					<tr id="wploti-ip-NEW" valign="middle"  class="<?php echo esc_attr($ip_row_class); ?>">
						<td class="column-wploti-ip-name">
							<input class="wploti_mr_disabled_field" type="text" id="wploti_mr_new_ip_name" name="wploti_mr_new_ip_name" placeholder="<?php esc_attr_e( "Enter Name:" , "maintenance-coming-soon-redirect-animation"); ?>">
							
						</td>
						<td class="column-wploti-ip-ip">
							<input class="wploti_mr_disabled_field" type="text" id="wploti_mr_new_ip_ip" name="wploti_mr_new_ip_ip" placeholder="<?php esc_attr_e( "Enter IP:" , "maintenance-coming-soon-redirect-animation"); ?>">
						</td>
						<td class="column-wploti-ip-active">&nbsp;</td>
						<td class="column-wploti-ip-creation-date">&nbsp;</td>
						<td class="column-wploti-actions">
							<span class='edit' id="wploti_mr_add_ip_link">
								<a href="#" class="wploti-mr-add-ip"><?php esc_html_e( "Add New IP" , "maintenance-coming-soon-redirect-animation"); ?></a>
							</span>
						</td>
					</tr>					
				</tbody>
			</table>
			<?php
		}
		
		/**
		 * (php)  genereate Access Key table data
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */		
		
		public function print_access_keys(){
			global $wpdb; ?>
			<table class="widefat fixed" cellspacing="0">
				<thead>
					<tr>
						<th class="column-wploti-ak-name"><?php esc_html_e( "Name" , "maintenance-coming-soon-redirect-animation"); ?></th>
						<th class="column-wploti-ak-email"><?php esc_html_e( "Email" , "maintenance-coming-soon-redirect-animation"); ?></th>
						<th class="column-wploti-ak-key"><?php esc_html_e( "Access Key" , "maintenance-coming-soon-redirect-animation"); ?></th>
						<th class="column-wploti-ak-key-creation-date"><?php esc_html_e( "Creation Date" , "maintenance-coming-soon-redirect-animation"); ?></th>
						<th class="column-wploti-ak-active"><?php esc_html_e( "Active" , "maintenance-coming-soon-redirect-animation"); ?></th>
						<th class="column-wploti-actions"><?php esc_html_e( "Actions" , "maintenance-coming-soon-redirect-animation"); ?></th>
					</tr>
				</thead>

				<tfoot>
					<tr>
						<th class="column-wploti-ak-name"><?php esc_html_e( "Name" , "maintenance-coming-soon-redirect-animation"); ?></th>
						<th class="column-wploti-ak-email"><?php esc_html_e( "Email" , "maintenance-coming-soon-redirect-animation"); ?></th>
						<th class="column-wploti-ak-key"><?php esc_html_e( "Access Key" , "maintenance-coming-soon-redirect-animation"); ?></th>
						<th class="column-wploti-ak-key-creation-date"><?php esc_html_e( "Creation Date" , "maintenance-coming-soon-redirect-animation"); ?></th>
						<th class="column-wploti-ak-active"><?php esc_html_e( "Active" , "maintenance-coming-soon-redirect-animation"); ?></th>
						<th class="column-wploti-actions"><?php esc_html_e( "Actions" , "maintenance-coming-soon-redirect-animation"); ?></th>
					</tr>
				</tfoot>
				
				<tbody>
					<?php
					
					$tbl = esc_sql($wpdb->prefix . $this->admin_options_name . '_access_keys');
					$sql = "SELECT * FROM $tbl ORDER BY name";
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					$codes = $wpdb->get_results($sql, OBJECT);
					$ak_row_class = 'alternate';
					if( $codes ){
						foreach( $codes as $code ) : 
							$ak_name = sanitize_text_field($code->name);
							$ak_email = sanitize_email($code->email);
							$ak_key = sanitize_text_field($code->access_key);
							$ak_creation_date = sanitize_text_field($code->created_at);
							$ak_code = sanitize_text_field($code->id);					
						?>
							<tr id="wploti-ak-<?php echo esc_attr($ak_code) ?>" valign="middle"  class="<?php echo esc_attr($ak_row_class) ?>">
								<td class="column-wploti-ak-name"><?php echo esc_html($ak_name); ?></td>
								<td class="column-wploti-ak-email"><a href="mailto:<?php echo esc_attr($ak_email) ?>" title="email : <?php echo esc_attr($ak_email) ?>"><?php echo esc_html($ak_email) ?></a></td>
								<td class="column-wploti-ak-key"><?php echo esc_html($ak_key); ?></td>
								<td class="column-wploti-ak-key-creation-date"><?php echo esc_html($ak_creation_date); ?></td>
								<td class="column-wploti-ak-active" id="wploti_mr_ak_status_<?php echo esc_attr($ak_code); ?>" ><?php echo ( $code->active == 1) ? '<span class="green">' .  esc_html__( "Yes" , "maintenance-coming-soon-redirect-animation") .' </span>' :  '<span class="red">' .  esc_html__( "No" , "maintenance-coming-soon-redirect-animation") .' </span>'; ?></td>
								<td class="column-wploti-actions">
									<span class='edit' id="wploti_mr_ak_status_<?php echo esc_attr($ak_code); ?>_action">
										<?php if( $code->active == 1 ){ ?>
											<a href="javascript:wploti_mr_toggle_ak( 0, <?php echo esc_attr($ak_code); ?> );"><?php esc_html_e( "Disable" , "maintenance-coming-soon-redirect-animation"); ?></a> | 
										<?php }else{ ?>
											<a href="javascript:wploti_mr_toggle_ak( 1, <?php echo esc_attr($ak_code); ?> );"><?php esc_html_e( "Enable" , "maintenance-coming-soon-redirect-animation"); ?></a> | 
										<?php } ?>
									</span>
									<span class='resend'>
										<a class='submitdelete' href="javascript:wploti_mr_resend_ak( <?php echo esc_attr($ak_code) ?>, '<?php echo esc_attr( addslashes($ak_name) ) ?>', '<?php echo esc_attr(addslashes($ak_email) ) ?>' );" ><?php esc_html_e( "Resend Code" , "maintenance-coming-soon-redirect-animation"); ?></a> | 
									</span>
									<span class='delete'>
										<a class='submitdelete' href="javascript:wploti_mr_delete_ak( <?php echo esc_attr($ak_code) ?>, '<?php echo esc_attr( addslashes($ak_name) ) ?>' );" ><?php esc_html_e( "Delete" , "maintenance-coming-soon-redirect-animation" ); ?></a>
									</span>
								</td>
							</tr>
							<?php
							$ak_row_class = ( $ak_row_class == '' ) ? 'alternate' : '';
						endforeach;
					}
					?>
					<tr id="wploti-ak-NEW" valign="middle"  class="<?php echo esc_attr($ak_row_class); ?>">
						<td class="column-wploti-ak-name">
							<input class="wploti_mr_disabled_field" type="text" id="wploti_mr_new_ak_name" name="wploti_mr_new_ak_name" placeholder="<?php esc_html_e( "Enter Name:" , "maintenance-coming-soon-redirect-animation"); ?>">
						</td>
						<td class="column-wploti-ak-email">
							<input class="wploti_mr_disabled_field" type="text" id="wploti_mr_new_ak_email" name="wploti_mr_new_ak_email" placeholder="<?php esc_html_e( "Enter Email:" , "maintenance-coming-soon-redirect-animation"); ?>">
						</td>
						<td class="column-wploti-ak-key">&nbsp;</td>
						<td class="column-wploti-ak-active">&nbsp;</td>
						<td class="column-wploti-ak-key-creation-date">&nbsp;</td>
						<td class="column-wploti-actions">
							<span class='edit' id="wploti_mr_add_ak_link">
								<a href="javascript:wploti_mr_add_new_ak( );"><?php esc_html_e( "Add New Access Key" , "maintenance-coming-soon-redirect-animation"); ?></a>
							</span>
						</td>
					</tr>					
				</tbody>
			</table>
			<?php
		}
		
		/**
		 * (php)  display activation notice
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */	
		
		public function display_status_if_active(){
			global $wpdb , $pagenow , $wploti_ajax_nonce ;				

			if ( get_option( 'wploti_activation_notice' ) && current_user_can('manage_options')) {
	
					// load the notices view
					$current_screen = get_current_screen();
					$current_user = wp_get_current_user();
					$settingslink = ( $current_screen->id != "settings_page_wploti-settings" ) ? ' <a href="'.wploti_admin_url.'">'.esc_html__( 'Settings' , 'maintenance-coming-soon-redirect-animation' ).'</a>' : '';
					$welcomemsg = '';
					$welcomemsg .='<div class="wploti-notice notice-success is-dismissable" id="wploti_enabled_notice">';
					$welcomemsg .='<lottie-player autoplay="true" loop src="'. esc_attr(IMG_path .'/wploti-bg.json').'"  class="wploti_animation"></lottie-player>';
					$welcomemsg .='<div class="notice-activation-text-wrapper">';
					/* translators: %s: Display name of the current user. */
					$welcomemsg .= '<p><h3 class="main_redirect_msg">' . sprintf( esc_html__( 'Thank you %s for installing Maintenance & Coming Soon Redirect Animation Plugin!', 'maintenance-coming-soon-redirect-animation' ), $current_user->display_name ) . '</h3></p>';
					$welcomemsg .='<span>'. esc_html__( "You can activate the Maintenance Mode in" , "maintenance-coming-soon-redirect-animation" ) . $settingslink . ' ' .  esc_html__( "or by Maintenance Status Top bar icon and choose your animation !", "maintenance-coming-soon-redirect-animation" ) .'</span>';
					$welcomemsg .='</div>';
					$welcomemsg .='<div class="wploti-dismiss"><a href="#dismiss" data-security="'. esc_attr($wploti_ajax_nonce) .'" name="wploti-activation-dismiss" class="wploti-activation-dismiss">' . esc_html__( "Dismiss" , "maintenance-coming-soon-redirect-animation" ) .' </a></div>';
					$welcomemsg .='</p></div>';

					$allowed_tags = [

						'div' => [
							'class' 	=> true,			
							'id'		=> true,			
						],
						'h3' => [
							'class'		=> true,			
						],
						'img' => [
							'src'		=> true,
							'class'     => array()
						],
						'a' => [
							'href'			 => true,
							'data-security'  => true,
							'name'	  		 => true,
							'class'   		 => true,		
						],
						'span' => [
							'class' => array(),
						],
						'lottie-player' => [
							'autoplay'  => true,
							'loop'      => true,
							'src' 		=> array(),
							'class'		=> array(),
						]
					
					];
					
					echo wp_kses($welcomemsg , $allowed_tags);

			}

			return;

		}

		/**
		 * (php)  remove jquery migrate console
		 *
		 * @since 2.0.0
		 * @access public
		 * @return void
		 */	

		public function remove_jquery_migrate_console($scripts) {
			if (!empty($scripts->registered['jquery'])) {
				$scripts->registered['jquery']->deps = array_diff($scripts->registered['jquery']->deps, ['jquery-migrate']);
			}
		}
		
		
		/**
		 * (php)  Add site health test
		 *
		 * @since 1.0.0
		 * @access public
		 * @return void
		 */		
		
		public function wploti_add_site_health( $tests ) {
			$tests['direct']['wploti_status'] = array(
				'label' => esc_html__( 'Maintenance', 'maintenance-coming-soon-redirect-animation' ),
				'test'  => array( $this, 'wploti_site_health' ),
			);
			return $tests;
		}


		/**
		 * (php)  verify site health status
		 *
		 * @since 1.0.0
		 * @access public
		 * @return array
		 */			

		public function wploti_site_health() {		
			
			global $wploti_header;
			
			$result = array(
				'label'       => esc_html__( 'Maintenance Redirect is not enabled' , 'maintenance-coming-soon-redirect-animation' ),
				'status'      => 'good',
				'badge'       => array(
					'label' => esc_html__( 'Visibility' , 'maintenance-coming-soon-redirect-animation' ),
					'color' => 'blue',
				),
				'description' => sprintf(
					'<p>%s</p>',
					esc_html__( 'Maintenance is not enabled and your site is visible to visitors.' , 'maintenance-coming-soon-redirect-animation' )
				),
				'actions'     => sprintf(
					'<p><a href="options-general.php?page=wploti-settings">%s</a></p>',
					esc_html__( 'Settings' , 'maintenance-coming-soon-redirect-animation' )
				),
				'test'        => 'wploti_status',
			);

			if ( $this->wploti_active() == '1' ) {
			
				if ( $wploti_header == "503" ) {

					$result['status'] = 'critical';
					$result['badge']['color'] = 'red';
					$result['label'] = esc_html__( 'Maintenance is enabled' , 'maintenance-coming-soon-redirect-animation' );
					$result['description'] = sprintf(
						'<p>%s</p>',
						esc_html__( 'Maintenance is enabled and your site is not visible to visitors. Your redirection type is set to 503, which could harm your Google ranking if left on for any length of time.' , 'maintenance-coming-soon-redirect-animation')
					
					);
				} else {

					if( $wploti_header != '503' ) :
						$result['status'] = 'recommended';
						$result['label'] = esc_html__( 'Maintenance Redirect is enabled' , 'maintenance-coming-soon-redirect-animation' );						
						$result['description'] = sprintf(
							'<p>%s</p>',
							/* translators: %s: The redirection type (e.g., "301 Moved Permanently"). */
							sprintf(
								/* translators: %s: The redirection type (e.g., "301 Moved Permanently"). */
								esc_html__( 'Maintenance is enabled and your site is not visible to visitors. Your redirection type is set to %s', 'maintenance-coming-soon-redirect-animation' ),
								$wploti_header
							)
						);
					
					endif;
				}
				
			}
			return $result;
		}

		/**
		 * (php)  Return active status 0 or 1
		 * 
		 * Default is 0 after plugin installation 
		 * @since 1.1.1
		 * @return string
		 */

		public function wploti_active() {		
			return get_option('wploti_status', '0') === '0' ? '0': '1';
		}

		public function header_tab(){ 
			
			global $wploti_ajax_nonce, $wploti_headers;
			$wploti_headers = $this->get_headers();

			?>

			<div class="wploti_mr_admin_section" >
				<h3 class="big-title"><?php esc_html_e( "Header Type:" , "maintenance-coming-soon-redirect-animation" ); ?></h3>
				<p><?php esc_html_e( "When redirect is enabled you can send different header types:" , "maintenance-coming-soon-redirect-animation" ); ?> </p>			
				<dl>

				<?php	foreach ( $wploti_headers as $header ) {  ?>

							<dt>
								<input type="radio" id="<?php echo esc_attr($header['code']) ?>" name="wploti_header_type" class="wploti_header_type" data-security="<?php echo esc_attr($wploti_ajax_nonce) ; ?>" <?php checked( get_option('wploti_header_type') ,  esc_attr($header['code']) ) ?> value="<?php echo esc_attr($header['code']) ?>">
								<label for="<?php echo esc_attr($header['code']) ?>">
								<?php echo esc_html($header['title']); ?>
								</label><br>
							</dt>
							<dd><?php  echo esc_html($header['description'])  ; ?></dd>

				<?php	} ?>
				
				</dl>
			</div>

		 <?php }   //header_tab

		public function ip_tab(){
			$user_ips = $this->get_user_ips();
			?>

			<div class="wploti_mr_admin_section" >
				<h3 class="big-title"><?php esc_html_e( "Unrestricted IP addresses:" , "maintenance-coming-soon-redirect-animation" ); ?>&nbsp;<span class="wploti_mr_small_dim">( <?php esc_html_e( "Your IPv4 address is:" , "maintenance-coming-soon-redirect-animation" ); ?>&nbsp;<?php echo esc_html( $user_ips['ipv4'] ? $user_ips['ipv4'] : __( 'Not detected', 'maintenance-coming-soon-redirect-animation' ) ); ?> - <?php esc_html_e( "Your IPv6 address is:" , "maintenance-coming-soon-redirect-animation" ); ?>&nbsp;<?php echo esc_html( $user_ips['ipv6'] ? $user_ips['ipv6'] : __( 'Not detected', 'maintenance-coming-soon-redirect-animation' ) ); ?> - <?php esc_html_e( "Your Class C is:" , "maintenance-coming-soon-redirect-animation" ); ?>&nbsp;<?php echo esc_html($this->get_user_class_c()); ?> )</span></h3>
				<p><?php esc_html_e( "Users with unrestricted IP addresses will bypass maintenance mode entirely. Using this option is useful to an entire office of clients to view the site without needing to jump through any extra hoops." , "maintenance-coming-soon-redirect-animation" ); ?></p> 
				
				<div id="wploti_mr_ip_tbl_container">
					<?php $this->print_unrestricted_ips(); ?>
				</div>
			</div>

		 <?php } //ip_tab /


		public function key_tab(){ ?>

			<div class="wploti_mr_admin_section">
				<h3 class="big-title"><?php esc_html_e( "Access Keys :" , "maintenance-coming-soon-redirect-animation"); ?></h3>
				<p><?php esc_html_e( "You can allow users temporary access by sending them the access key. When a new key is created, a link to create the access key session will be emailed to the email address provided. Access can then be revoked either by disabling or deleting the key." , "maintenance-coming-soon-redirect-animation" ); ?></p>
				
				<div id="wploti_mr_ak_tbl_container">
					<?php $this->print_access_keys(); ?>
				</div>
			</div>

		 <?php } 

		public function password_tab(){
			$access_password = $this->get_access_password();
			?>
			<div class="wploti_mr_admin_section">
				<style>
					#wploti-password-strength {
						box-sizing: border-box;
						max-width: 525px;
						min-height: 36px;
						padding: 8px 12px;
						border: 1px solid transparent;
						font-weight: 600;
						text-align: center;
						width: 350px;
					}
					#wploti-password-strength.bad,
					#wploti-password-strength.short {
						border-color: #d63638;
						background: #f7c7c7;
						color: #8a1f11;
					}
					#wploti-password-strength.good {
						border-color: #dba617;
						background: #fceec2;
						color: #6b4e00;
					}
					#wploti-password-strength.strong {
						border-color: #00a32a;
						background: #b8e8c1;
						color: #135e1f;
					}
					input.wploti-access-password{
						border-radius: 0;
						margin: 0;
					}
					input.wploti-access-password.strong,
					input.wploti-access-password.strong:focus {						
						border: 1px solid #00a32a;
						border-bottom: 0;
						box-shadow: none;
					}
					input.wploti-access-password.bad,
					input.wploti-access-password.bad:focus,
					input.wploti-access-password.short
					input.wploti-access-password.short:focus {
						border: 1px solid #d63638;
						border-bottom: 0;
						box-shadow: none;
					}
					input.wploti-access-password.good,
					input.wploti-access-password.good:focus {
						border: 1px solid #dba617;
						border-bottom: 0;
						box-shadow: none;
					}
				</style>
				<h3 class="big-title"><?php esc_html_e( 'Password Access :', 'maintenance-coming-soon-redirect-animation' ); ?></h3>
				<p><?php esc_html_e( 'Allow visitors to enter one shared password to preview the website. Password access lasts for 24 hours in each browser.', 'maintenance-coming-soon-redirect-animation' ); ?></p>
				<form id="wploti-password-form" style="max-width:520px;">
					<p><label for="wploti-access-password"><strong><?php esc_html_e( 'Password', 'maintenance-coming-soon-redirect-animation' ); ?></strong></label></p>
					<div class="wp-pwd wploti-password-picker">
						<span class="password-input-wrapper">
							<input type="password" id="wploti-access-password" name="wploti_access_password" value="<?php echo esc_attr( $access_password ); ?>" placeholder="<?php esc_attr_e( 'Enter or generate a password', 'maintenance-coming-soon-redirect-animation' ); ?>" class="regular-text wploti-access-password" autocomplete="new-password" aria-describedby="wploti-password-strength">
							<button type="button" class="button wp-hide-pw" aria-label="<?php esc_attr_e( 'Show password', 'maintenance-coming-soon-redirect-animation' ); ?>"><span class="dashicons dashicons-visibility" aria-hidden="true"></span><span class="wp-hide-pw-text"><?php esc_html_e( 'Show', 'maintenance-coming-soon-redirect-animation' ); ?></span></button>
							<button type="button" class="button wp-cancel-pw" style="display:none;"><?php esc_html_e( 'Cancel', 'maintenance-coming-soon-redirect-animation' ); ?></button>
							<button type="button" class="button wploti-copy-password" aria-label="<?php esc_attr_e( 'Copy password', 'maintenance-coming-soon-redirect-animation' ); ?>" title="<?php esc_attr_e( 'Copy password', 'maintenance-coming-soon-redirect-animation' ); ?>"><span class="dashicons dashicons-admin-page" aria-hidden="true"></span></button>
						</span>
						<button type="button" class="button wp-generate-pw"><?php esc_html_e( 'Generate Password', 'maintenance-coming-soon-redirect-animation' ); ?></button>
					</div>
					<div id="wploti-password-strength" class="password-strength" aria-live="polite"></div>
					<p id="wploti-password-status" class="description"><?php echo $access_password ? esc_html__( 'A password is configured. Use Show to view it or enter a new password to replace it.', 'maintenance-coming-soon-redirect-animation' ) : esc_html__( 'Generate or enter a password, then save it.', 'maintenance-coming-soon-redirect-animation' ); ?></p>
					<p><label><input type="checkbox" name="wploti_password_enabled" value="1" <?php checked( '1', get_option( 'wploti_password_enabled', '0' ) ); ?>> <?php esc_html_e( 'Enable password access', 'maintenance-coming-soon-redirect-animation' ); ?></label></p>
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Password Settings', 'maintenance-coming-soon-redirect-animation' ); ?></button>
					<span id="wploti-password-result" style="margin-left:10px;"></span>
				</form>
				<script>
				jQuery(function($) {
					var password = $('#wploti-access-password');
					var strength = $('#wploti-password-strength');
					var input = $('input.wploti-access-password');
					var generate = $('.wploti-password-picker .wp-generate-pw');
					var cancel = $('.wploti-password-picker .wp-cancel-pw');
					var hide = $('.wploti-password-picker .wp-hide-pw');
					var copy = $('.wploti-password-picker .wploti-copy-password');
					var originalPassword = password.val();
					var strengthLabels = {
						'-1': '<?php echo esc_js( __( 'Very weak', 'maintenance-coming-soon-redirect-animation' ) ); ?>',
						'0': '<?php echo esc_js( __( 'Very weak', 'maintenance-coming-soon-redirect-animation' ) ); ?>',
						'1': '<?php echo esc_js( __( 'Weak', 'maintenance-coming-soon-redirect-animation' ) ); ?>',
						'2': '<?php echo esc_js( __( 'Weak', 'maintenance-coming-soon-redirect-animation' ) ); ?>',
						'3': '<?php echo esc_js( __( 'Medium', 'maintenance-coming-soon-redirect-animation' ) ); ?>',
						'4': '<?php echo esc_js( __( 'Strong', 'maintenance-coming-soon-redirect-animation' ) ); ?>'
					};

					function updateStrength() {
						var value = password.val();
						copy.prop('disabled', !value);
						if (!value) { 
							strength.removeClass('short bad good strong').empty(); 
							input.removeClass('short bad good strong').empty();
							return; 
						}
						var score = wp.passwordStrength.meter(value, [], value);
						strength.removeClass('short bad good strong').addClass(score < 2 ? 'bad' : score < 4 ? 'good' : 'strong').text(strengthLabels[score] || strengthLabels[0]);
						input.removeClass('short bad good strong').addClass(score < 2 ? 'bad' : score < 4 ? 'good' : 'strong').text(strengthLabels[score] || strengthLabels[0]);
					}

					generate.on('click', function() {
						wp.ajax.post('generate-password').done(function(response) {
							password.val(response).attr('type', 'text').trigger('input');
							generate.hide(); cancel.show(); hide.find('.wp-hide-pw-text').text('<?php echo esc_js( __( 'Hide', 'maintenance-coming-soon-redirect-animation' ) ); ?>');
						});
					});
					copy.on('click', function() {
						var value = password.val();
						if (!value || !navigator.clipboard) { return; }
						navigator.clipboard.writeText(value).then(function() {
							$('#wploti-password-status').text('<?php echo esc_js( __( 'Password copied to clipboard.', 'maintenance-coming-soon-redirect-animation' ) ); ?>');
						});	
					});
					hide.on('click', function() {
						var showing = password.attr('type') === 'text';
						password.attr('type', showing ? 'password' : 'text');
						hide.find('.dashicons').toggleClass('dashicons-visibility dashicons-hidden');
						hide.find('.wp-hide-pw-text').text(showing ? '<?php echo esc_js( __( 'Show', 'maintenance-coming-soon-redirect-animation' ) ); ?>' : '<?php echo esc_js( __( 'Hide', 'maintenance-coming-soon-redirect-animation' ) ); ?>');
					});
					cancel.on('click', function() { password.val(originalPassword).attr('type', 'password').trigger('input'); generate.show(); cancel.hide(); });
					password.on('input', updateStrength);
					//updateStrength();
				});

				jQuery('#wploti-password-form').on('submit', function(event) {
					event.preventDefault();
					var form = jQuery(this);
					jQuery.post(ajaxurl, {
						action: 'wploti_save_password',
						security: '<?php echo esc_attr( wp_create_nonce( 'wploti_nonce' ) ); ?>',
						wploti_access_password: form.find('[name="wploti_access_password"]').val(),
						wploti_password_enabled: form.find('[name="wploti_password_enabled"]').is(':checked') ? '1' : '0'
					}, function(response) {
						jQuery(".updated").text('<?php echo esc_js( __( 'Password Settings saved successfully.', 'maintenance-coming-soon-redirect-animation' ) ); ?>').fadeIn(1000).delay(7000).fadeOut("slow");
						if (response === 'SUCCESS' && form.find('[name="wploti_access_password"]').val()) {
							jQuery('#wploti-password-status').text('<?php echo esc_js( __( 'Password saved. Use Copy Password to share it securely.', 'maintenance-coming-soon-redirect-animation' ) ); ?>');
						}
					});
				});
				</script>
			</div>
		<?php }


		public function animation_tab(){ 
			
			?>

			<div class="wploti_mr_admin_section">	
				<h3 class="big-title"><?php esc_html_e( "Maintenance Animation :" , "maintenance-coming-soon-redirect-animation"); ?></h3>
				
				<h4 class="small-title"><?php esc_html_e( "Active Animation :" , "maintenance-coming-soon-redirect-animation"); ?></h4>

				<div class="selected-animation">										
					<lottie-player autoplay="true" loop src="<?php echo esc_attr( get_option("wploti_animation", 'default-animation.json') ) ?>" class="lottieanimation"></lottie-player>
				</div>

				<?php $this->upload_animation(); ?>

				<h4 class="small-title"><?php esc_html_e( "Or select one from the animations library :" , "maintenance-coming-soon-redirect-animation" ); ?></h4>
				<?php $animations = array_slice(scandir(__DIR__.'/animations'),2); ?>
				<div animations-count="<?php echo esc_attr(count($animations)); ?>" class="animations"></div>

				<div id ="load-animations-message"></div>
			
			</div>

		 <?php } 

		public function wploti_mime_types($mimes) {
			$mimes['json'] = 'application/json';
			return $mimes; 
		} 

		public function upload_animation(){
			$wploti_upload_animation_nonce = wp_create_nonce( "wploti_upload_animation_nonce" );
			?>

				<div class="img-select-container" style="display: flex; align-items: center; gap: 40px;">
					<input id="upload_image" type="hidden" size="36" name="wploti_upload_animation" value="<?php echo esc_attr(get_option('wploti_upload_animation')); ?>" />
					<input type="button" data-security="<?php echo esc_attr($wploti_upload_animation_nonce) ?>" name="wploti_upload_animation" accept="*.json, *.gif" class="button button-secondary upload-button" value="<?php echo esc_attr( "Upload Your own animation" , "maintenance-coming-soon-redirect-animation"); ?>" data-group="1">
					<?php esc_html_e( "Please upload your animation in JSON format, otherwise it will not be displayed" , "maintenance-coming-soon-redirect-animation" ); ?>
				</div>
					<?php 

					wp_enqueue_media(); ?>

					<script>
						jQuery(document).ready( function($) {

							// Uploading files
							var mediaUploader;

							$('.upload-button').on('click', function( event ){

								event.preventDefault();

								var buttonID = $(this).data('id');
								var security = $(this).data('security');

								// If the media frame already exists, reopen it.
								if ( mediaUploader ) {
									// Open frame
									mediaUploader.id = buttonID;
									mediaUploader.open();
									return;
								}

								// Create the media frame.
								mediaUploader = wp.media.frames.file_frame = wp.media({
									id: buttonID,
									title: 'Select a file to upload',
									button: {
										text: 'Select',
									},
									multiple: false , // Set to true to allow multiple files to be selected
									uploader: {
									type: 'text/plain'
									}
								});

								// When an image is selected, run a callback.
								mediaUploader.on( 'select', function() {
									
									attachment = mediaUploader.state().get('selection').first().toJSON();				

									var uploaded_animation = attachment.url;

									$('.selected-bg').remove();

									$('.selected-animation > lottie-player').remove();
									$('.selected-animation').append(
										$('<lottie-player/>')
										.attr("autoplay", "true")
										.attr("loop", "true")
										.attr("src", uploaded_animation)							                       
									)    
									
									$.ajax({
										url: ajaxurl,
										data: {
											uploaded_animation: uploaded_animation,
											action: 'wploti_uploaded_animation_save',
											security: security								
										},
										type: 'post',
							
										success: function (result, textstatus) {
											/* console.log(result);
											console.log('sucess'); */
											
										},
										error: function (result) {
											/* console.log(result);
											console.log('fail'); */
										},
							
									})

								});


								// Finally, open the modal
								mediaUploader.open();
							});


						});
					</script>
			<?php
		}

		/**
		 * Save uploaded animation option securely
		 *
		 * @since 2.0.0
		 * @access public
		 * @return void
		 */
		public function wploti_uploaded_animation_save_option() {
			// Verify nonce
			check_ajax_referer('wploti_upload_animation_nonce', 'security');
			
			// Verify user has proper permissions
			if (!current_user_can('manage_options')) {
				wp_die('Unauthorized access', 'Access Denied', array('response' => 403));
			}
			
			// Sanitize input
			$uploaded_animation = isset($_POST['uploaded_animation']) ? sanitize_text_field(wp_unslash($_POST['uploaded_animation'])) : '';

			// Update option
			update_option('wploti_animation', $uploaded_animation);

			wp_die();
		}

		/**
		 * Add whitelisted roles option securely
		 *
		 * @since 2.0.0
		 * @access public
		 * @return void
		 */
		public function wploti_add_whitelisted_roles_option() {
			// Verify nonce
			check_ajax_referer('wploti_nonce', 'security');
			
			// Verify user has proper permissions
			if (!current_user_can('manage_options')) {
				wp_die('Unauthorized access', 'Access Denied', array('response' => 403));
			}

			if (isset($_POST['role'])) {
				// Get existing whitelisted roles, ensure it's an array
				$wploti_whitelisted_roles = get_option('wploti_whitelisted_roles', array());
				if (!is_array($wploti_whitelisted_roles)) {
					$wploti_whitelisted_roles = array();
				}
				
				// Sanitize role input
				$whitelisted_role = sanitize_text_field(wp_unslash($_POST['role']));
				
				// Verify the role exists in WordPress
				$all_roles = wp_roles()->get_names();
				if (array_key_exists($whitelisted_role, $all_roles) && !in_array($whitelisted_role, $wploti_whitelisted_roles)) {
					$wploti_whitelisted_roles[] = $whitelisted_role;
					update_option('wploti_whitelisted_roles', $wploti_whitelisted_roles);
					wp_send_json_success(array('message'=> __('Role(s) have been successfully whitelisted', 'maintenance-coming-soon-redirect-animation')));
				}
			}

			wp_die();
		}

		/**
		 * Remove whitelisted roles option securely
		 *
		 * @since 2.0.0
		 * @access public
		 * @return void
		 */
		public function wploti_remove_whitelisted_roles_option() {
			// Verify nonce
			check_ajax_referer('wploti_nonce', 'security');
			
			// Verify user has proper permissions
			if (!current_user_can('manage_options')) {
				wp_die('Unauthorized access', 'Access Denied', array('response' => 403));
			}

			if (isset($_POST['role'])) {
				// Get existing whitelisted roles, ensure it's an array
				$wploti_whitelisted_roles = get_option('wploti_whitelisted_roles', array());
				if (!is_array($wploti_whitelisted_roles)) {
					$wploti_whitelisted_roles = array();
				}
				
				// Sanitize role input
				$whitelisted_role = sanitize_text_field(wp_unslash($_POST['role']));

				// Delete element key that value match $whitelisted_role
				foreach (array_keys($wploti_whitelisted_roles, $whitelisted_role, true) as $key) {
					unset($wploti_whitelisted_roles[$key]);
				}

				// Re-index array
				$wploti_whitelisted_roles = array_values($wploti_whitelisted_roles);
				
				// Update the option
				update_option('wploti_whitelisted_roles', $wploti_whitelisted_roles);
				wp_send_json_success(array('message'=> __('Role(s) have been successfully removed from the whitelist', 'maintenance-coming-soon-redirect-animation')));
			}

			wp_die();
		}

		/**
		 * Add whitelisted users option securely
		 *
		 * @since 2.0.0
		 * @access public
		 * @return void
		 */
		public function wploti_add_whitelisted_users_option() {
			// Verify nonce
			check_ajax_referer('wploti_nonce', 'security');
			
			// Verify user has proper permissions
			if (!current_user_can('manage_options')) {
				wp_die('Unauthorized access', 'Access Denied', array('response' => 403));
			}

			if (isset($_POST['user_id'])) {
				// Get existing whitelisted users, ensure it's an array
				$wploti_whitelisted_users = get_option('wploti_whitelisted_users', array());
				if (!is_array($wploti_whitelisted_users)) {
					$wploti_whitelisted_users = array();
				}
				
				// Sanitize user ID input
				$whitelisted_user = absint(wp_unslash($_POST['user_id']));
				
				// Verify the user exists
				if (get_userdata($whitelisted_user) && !in_array($whitelisted_user, $wploti_whitelisted_users)) {
					$wploti_whitelisted_users[] = $whitelisted_user;
					update_option('wploti_whitelisted_users', $wploti_whitelisted_users);
					wp_send_json_success(array('message'=> __('User(s) have been successfully whitelisted', 'maintenance-coming-soon-redirect-animation')));
				}
			}

			wp_die();
		}

		/**
		 * Remove whitelisted users option securely
		 *
		 * @since 2.0.0
		 * @access public
		 * @return void
		 */
		public function wploti_remove_whitelisted_users_option() {
			// Verify nonce
			check_ajax_referer('wploti_nonce', 'security');
			
			// Verify user has proper permissions
			if (!current_user_can('manage_options')) {
				wp_die('Unauthorized access', 'Access Denied', array('response' => 403));
			}

			if (isset($_POST['user_id'])) {
				// Get existing whitelisted users, ensure it's an array
				$wploti_whitelisted_users = get_option('wploti_whitelisted_users', array());
				if (!is_array($wploti_whitelisted_users)) {
					$wploti_whitelisted_users = array();
				}
				
				// Sanitize user ID input
				$whitelisted_user = absint(wp_unslash($_POST['user_id']));

				// Delete element key that value match $whitelisted_user
				foreach (array_keys($wploti_whitelisted_users, $whitelisted_user, true) as $key) {
					unset($wploti_whitelisted_users[$key]);
				}

				// Re-index array
				$wploti_whitelisted_users = array_values($wploti_whitelisted_users);
				
				// Update the option
				update_option('wploti_whitelisted_users', $wploti_whitelisted_users);
				wp_send_json_success(array('message'=> __('User(s) have been successfully removed from the whitelist', 'maintenance-coming-soon-redirect-animation')));
			}

			wp_die();
		}
	
		public function message_tab() { 
			
			global $wploti_ajax_nonce;

			?>
			
			<div id="wploti_text_message">
			<strong><?php esc_html_e( "Maintenance Mode Message (optional) :" , "maintenance-coming-soon-redirect-animation"); ?></strong>
				<p><?php esc_html_e( "You can write a brief message that will be displayed under animation :" , "maintenance-coming-soon-redirect-animation"); ?></p>
				<?php
				 $wploti_message = esc_html__( 'This site is currently undergoing maintenance. Please check back later', 'maintenance-coming-soon-redirect-animation' );
				 wp_editor( get_option('wploti_message', $wploti_message), 'content', array('tabfocus_elements' => 'insert-media-button,save-post', 'editor_height' => 250, 'resize' => 1, 'editor_class' => 'wploti_message', 'textarea_name' => 'wploti_message', 'drag_drop_upload' => 1)); ?>
			</div>
			

		 <?php } 

		public function extra_tab(){ 
			$wploti_ajax_nonce = wp_create_nonce( "wploti_nonce" );
			?>

			<div class="wploti-tab-content">
				<table class="form-table"><tbody>
					<?php //Whitelisted User Roles  
					$wploti_whitelisted_roles[] = get_option('wploti_whitelisted_roles');
					$wploti_whitelisted_users[] = get_option('wploti_whitelisted_users');
					$wploti_roles = new WP_Roles();
					$roles = $wploti_roles->get_names();
					$users = array();
					
					?>
					<tr valign="top">					
						<th scope="row"><?php esc_html_e( "Whitelisted User Roles" , "maintenance-coming-soon-redirect-animation"); ?> :</th>
						<td>
							<?php
							$current_user = wp_get_current_user();							

								foreach ($roles as $role_value => $role_label) {

									?>
									<input 
										type="checkbox" 
										value="<?php echo esc_attr($role_value); ?>" 
										class="wploti_whitelisted_roles"
										data-security="<?php echo esc_attr($wploti_ajax_nonce) ?>"
										<?php if(in_array( $role_value , $wploti_whitelisted_roles[0] )){
											echo "checked";
										}?>
									/>
									<label>
										<?php echo esc_html($role_label); ?>
									</label>
									<br />
							<?php } 
							?>
						</td>
						
					</tr>
					<tr class="description">						
					<td colspan="2">
						<p><?php echo wp_kses_post(__('Selected user roles will <b>not</b> be affected by the maintenance mode and will always see the "normal" site. Default: administrator.', 'maintenance-coming-soon-redirect-animation')); ?></p>
					</td>
					</tr>
					
					<?php //Whitelisted Users 

					$tmp_users = get_users(array('fields' => array('id', 'display_name')));
					foreach ($tmp_users as $user) {
						$users[] = array('val' => $user->id, 'label' => $user->display_name);
					}
					?>			
					<tr valign="top">
					
						<th scope="row">
							<label for="wploti_whitelisted_users"><?php esc_html_e( "Whitelisted Users :" , "maintenance-coming-soon-redirect-animation"); ?></label>
						</th>
						<td>
						<select id="wploti_whitelisted_users" data-security="<?php echo esc_attr($wploti_ajax_nonce) ?>" class="select2" style="width: 100%; max-width: 300px;" name="wploti_whitelisted_users" multiple>						
						<?php 
							$this->create_wploti_select_options($users , get_option('wploti_whitelisted_users') , true) ;
						?>
						</select>	
						</td>

					</tr>
					<tr class="description">
					<td colspan="2">
						<p><?php echo wp_kses_post(__('Selected users (when logged in) will <b>not</b> be affected by the maintenance mode and will always see the "normal" site.', 'maintenance-coming-soon-redirect-animation')); ?></p>
					</td>
					</tr>
					<tr valign="top">
						<th scope="row"><label for="wploti-custom-login-slug"><?php esc_html_e( 'Custom Login URL', 'maintenance-coming-soon-redirect-animation' ); ?></label></th>
						<td>
							<label><input type="checkbox" id="wploti-custom-login-enabled" data-security="<?php echo esc_attr( $wploti_ajax_nonce ); ?>" <?php checked( '1', get_option( 'wploti_custom_login_enabled', '0' ) ); ?>> <?php esc_html_e( 'Restrict direct access to wp-login.php', 'maintenance-coming-soon-redirect-animation' ); ?></label>
							<p><code><?php echo esc_html( home_url( '/' ) ); ?></code><input type="text" id="wploti-custom-login-slug" value="<?php echo esc_attr( $this->get_custom_login_slug() ); ?>" data-base-url="<?php echo esc_url( home_url( '/' ) ); ?>" placeholder="private-login" class="regular-text1" autocomplete="off"><button type="button" id="wploti-copy-custom-login-url" class="button" aria-label="<?php esc_attr_e( 'Copy login URL', 'maintenance-coming-soon-redirect-animation' ); ?>" title="<?php esc_attr_e( 'Copy login URL', 'maintenance-coming-soon-redirect-animation' ); ?>"><span class="dashicons dashicons-admin-page" aria-hidden="true"></span></button> <button type="button" id="wploti-generate-custom-login-slug" class="button"><?php esc_html_e( 'Generate URL', 'maintenance-coming-soon-redirect-animation' ); ?></button></p>
							<p class="description"><?php esc_html_e( 'Use this URL to sign in. Direct requests to wp-login.php will be redirected to the home page.', 'maintenance-coming-soon-redirect-animation' ); ?></p>
							<p><label><input type="checkbox" id="wploti-private-login-enabled" <?php checked( '1', get_option( 'wploti_private_login_enabled', '1' ) ); ?>> <?php esc_html_e( 'Show private login form on the maintenance page', 'maintenance-coming-soon-redirect-animation' ); ?></label></p>
							<button type="button" id="wploti-save-custom-login" class="button button-secondary"><?php esc_html_e( 'Save Login Settings', 'maintenance-coming-soon-redirect-animation' ); ?></button>
							<span id="wploti-custom-login-result" aria-live="polite"></span>
						</td>
					</tr>
					
				</table>
			</div>
					
		<?php
		  }  

		/**
		 * (php)  check if user has the specified role
		 *
		 * @since 2.0.0
		 * @access public
		 * @return boolean
		 */
		public function user_has_role($roles){
			$current_user = wp_get_current_user();

			if ($current_user->roles) {
				$user_role = $current_user->roles[0];
			} else {
				$user_role = 'guest';
			}

			return in_array($user_role, $roles);
		} // user_has_role

		
		/**
		 * (php)  helper function for creating dropdowns
		 *
		 * @since 2.0.0
		 * @access public
		 * @return boolean
		 */
		public function create_wploti_select_options($options, $selected = null, $output = true)
		{
			$out = "\n";

			if (!is_array($selected)) {
				$selected = array($selected);
			}

			foreach ($options as $tmp) {
				$data = '';
				if (isset($tmp['disabled'])) {
					$data .= ' disabled="disabled" ';
				}
				if (in_array($tmp['val'], $selected)) {
					$out .= "<option selected=\"selected\" value=\"{$tmp['val']}\"{$data}>{$tmp['label']}&nbsp;</option>\n";
				} else {
					$out .= "<option value=\"{$tmp['val']}\"{$data}>{$tmp['label']}&nbsp;</option>\n";
				}
			} // foreach

			if ($output) {
				//echo ($out);
				echo wp_kses( $out,
				array(
					'option' => array(
						'selected' => array(),
						'value' => array(),
						'rel' => array(),
					)
				)
			);
			} else {
				return $out;
			}
		} // create_wploti_select_options


		/**
		 * (php)  create the admin page
		 *
		 * @since 1.0.0
		 * @access public
		 * @return array
		 */
		
		public function print_admin_page() {
			global $wpdb;
			global $wploti_ajax_nonce;

			// display update notice 
			echo '<div class="updated" style="display: none" ><p><strong>'. esc_html__("Settings Saved" , "maintenance-coming-soon-redirect-animation" ) .'</strong></p></div>';

			 ?>

			<!-- **************  JS  ************** -->
			
			<script type="text/javascript" charset="utf-8">
				
				/**
				 * (js) custom alert
				 *
				 * @since 1.0.0
				 * @param string msg1
				 * @param string msg2
				 * @return string
				 */	
				
				function wploti_alert(msg1, msg2) {
					var alertContent = `
					<div class="modal">
						<div class="modal-content">
							<div class="modal-header">											
							<?php // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
								<img class="alert-icon" src="<?php echo esc_attr(plugin_dir_url(__FILE__) . '/images/alert-icon.png'); ?>" alt="Alert Icon" />
							</div>
							<div class="messages">
								<div>${msg1}</div>
								<div>${msg2}</div>							
							</div>
							<div class="modal-footer">
								<button value="Reset settings" class="button button-primary ok_wploti_alert" name="ok_wploti_alert">OK</button>
							</div>
						</div>
					</div>`
					var modal = document.createElement("div");
					modal.innerHTML = alertContent;
					document.body.appendChild(modal); 

					jQuery('.ok_wploti_alert').click( function(){
						jQuery('.modal').fadeOut('1000');
					})
				}

				function wploti_alert_arr(errors) {
					var alertContent = `
					<div class="modal">
						<div class="modal-content">
							<div class="modal-header">
								<img class="alert-icon" src="<?php echo esc_attr(plugin_dir_url(__FILE__) . '/images/alert-icon.png'); ?>" alt="Alert Icon" />
							</div>
							<div class="messages">
								${errors.map(err => `<div>${err}</div>`).join('')}
							</div>
							<div class="modal-footer">
								<button class="button button-primary ok_wploti_alert" name="ok_wploti_alert">OK</button>
							</div>
						</div>
					</div>`;
					var modal = document.createElement("div");
					modal.innerHTML = alertContent;
					document.body.appendChild(modal); 

					jQuery('.ok_wploti_alert').click(function(){
						jQuery('.modal').fadeOut('1000');
					});
				}


				/**
				 * (js) custom confirm
				 *
				 * @since 1.0.0
				 * @param {object options} The properties to create the element with
				 * @return object
				 */	

				const wploti_confirm = {
					open (options) {
						options = Object.assign({}, {
							message: '',
							okText: 'OK',
							cancelText: 'Cancel',
							onok: function () {},
							oncancel: function () {}
						}, options);
					
			
						var confirmContent = `
						<div class="modal">
							<div class="modal-content">
								<div class="modal-header">
									<?php // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
									<img class="alert-icon" src="<?php echo esc_attr ( plugin_dir_url( __FILE__ ).'/images/alert-icon.png' )?>" alt="Alert Icon" />
								</div>
								<div class="messages">
									<div>${options.message}</div>
								</div>
								<div class="modal-footer">
									<button value="OK" class="button button-primary ok_wploti_confirm" name="ok_wploti_alert">${options.okText}</button>
									<button value="${options.cancelText}" class="button button-secondary cancel_wploti_confirm" name="cancel_wploti_alert"><?php esc_html_e('Cancel', 'maintenance-coming-soon-redirect-animation' ) ?></button>
								</div>
							</div>
						</div>`
						var modal = document.createElement("div");
						modal.innerHTML = confirmContent;
						document.body.appendChild(modal); 

						jQuery('.cancel_wploti_confirm').click( function(){
							options.oncancel();
							jQuery('.modal').fadeOut('1000');
						})

						jQuery('.ok_wploti_confirm').click( function(){
							options.onok();
							jQuery('.modal').fadeOut('1000');
						})
					}
				}
				
				/**
				 * (js) add new Access Key
				 *
				 * @since 1.0.0
				 * @param ak_email string
				 * @return void
				 */
				
				function wploti_mr_add_new_ak() {
					// Validate entries before posting AJAX call
					var error_msg = '';
					var ak_name = jQuery('#wploti_mr_new_ak_name').val();
					var ak_email = jQuery('#wploti_mr_new_ak_email').val();

					// Check if the name is empty or default
					if (ak_name === '' || ak_name === '<?php esc_html_e("Enter Name:", "maintenance-coming-soon-redirect-animation"); ?>') {
						error_msg += '<?php esc_html_e("You must enter a Name", "maintenance-coming-soon-redirect-animation"); ?>.<br>\n';
					}

					// Check if the email is empty or default
					if (ak_email === '' || ak_email === '<?php esc_html_e("Enter Email:", "maintenance-coming-soon-redirect-animation"); ?>') {
						error_msg += '<?php esc_html_e("You must enter an Email", "maintenance-coming-soon-redirect-animation"); ?>.<br>\n';
					} else if (!isValidEmail(ak_email)) {
						// Validate email format using a regular expression
						error_msg += '<?php esc_html_e("You must enter a valid Email address", "maintenance-coming-soon-redirect-animation"); ?>.<br>\n';
					}

					// Display error message if validation fails
					if (error_msg !== '') {
						wploti_alert('<?php esc_html_e("There is a problem with the information you have entered", "maintenance-coming-soon-redirect-animation"); ?>.\n\n', error_msg);
					} else {
						// Proceed with confirmation and AJAX request
						wploti_confirm.open({
							message: '<?php esc_html_e("You are about to email an Access Key link to", "maintenance-coming-soon-redirect-animation"); ?> <b>' + ak_email + '</b>!<br> <?php esc_html_e("If you do not see the email in a few seconds,", "maintenance-coming-soon-redirect-animation"); ?> <br> <?php esc_html_e("Please check your “junk mail” or “spam” folder.", "maintenance-coming-soon-redirect-animation"); ?> \n\n',
							onok: () => {
								// Prepare AJAX data
								var data = {
									action: 'wploti_mr_add_ak',
									security: '<?php echo esc_attr($wploti_ajax_nonce); ?>',
									wploti_mr_ak_name: ak_name,
									wploti_mr_ak_email: ak_email
								};

								// Set section to loading image
								var img_url = '<?php echo esc_attr(plugins_url('images/ajax_loader_16x16.gif', __FILE__)); ?>';
								<?php // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
								jQuery('#wploti_mr_ak_tbl_container').html('<img src="' + img_url + '">');

								// Send AJAX request
								jQuery.post(ajaxurl, data, function(response) {
									jQuery('#wploti_mr_ak_tbl_container').html(response);
								});
							}
						});
					}
				}

				/**
				 * (js) validate email format
				 *
				 * @since 2.3.0
				 * @param emails string
				 * @return bool
				 */
				function isValidEmail(email) {
					// Regular expression for basic email validation
					var emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
					return emailRegex.test(email);
				}

				/**
				 * (js) toggle Access Key status ( Enable || disable  Access Key )
				 *
				 * @since 1.0.0
				 * @param status string
				 * @param ak_id number
				 * @return void
				 */
				
				function wploti_mr_toggle_ak ( status, ak_id ) {
					// prepare ajax data
					var data = {
						action:			'wploti_mr_toggle_ak',
						security:			'<?php  echo esc_attr($wploti_ajax_nonce); ?>',
						wploti_mr_ak_active: 	status,
						wploti_mr_ak_id:     	ak_id 
					};

					// set status to loading img					
					var img_url = '<?php echo esc_attr(plugins_url( 'images/ajax_loader_16x16.gif', __FILE__ )); ?>';
					<?php // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
					jQuery( '#wploti_mr_ak_status_' + ak_id ).html('<img src="' + img_url + '">');

					// send ajax request
					jQuery.post( ajaxurl, data, function(response) {
						var split_response = response.split('|');
						if( split_response[0] == 'SUCCESS' ){
							var ak_id     = split_response[1];
							var ak_active = split_response[1];
							// update divs / 1 = id / 2 = status
							if( split_response[2] == '1' ){
								// active
								jQuery('#wploti_mr_ak_status_' + split_response[1] ).html( '<span class="green">Yes</span>' );
								jQuery('#wploti_mr_ak_status_' + split_response[1] + '_action' ).html( '<a href="javascript:wploti_mr_toggle_ak( 0, ' + split_response[1] + ' );"><?php esc_html_e( "Disable" , "maintenance-coming-soon-redirect-animation"); ?></a> | ' );
							}else{
								// disabled
								jQuery('#wploti_mr_ak_status_' + split_response[1] ).html( '<span class="red">No</span>' );
								jQuery('#wploti_mr_ak_status_' + split_response[1] + '_action' ).html( '<a href="javascript:wploti_mr_toggle_ak( 1, ' + split_response[1] + ' );"><?php esc_html_e( "Enable" , "maintenance-coming-soon-redirect-animation"); ?></a> | ' );
							} 
						}else{
							wploti_alert( '<?php esc_html_e( "There was a database error. Please reload this page" , "maintenance-coming-soon-redirect-animation"); ?>' , ' ' );
						}
					});
				}

				/**
				 * (js) delete Access Key
				 *
				 * @since 1.0.0
				 * @param ak_id number
				 * @param ak_name string
				 * @return void
				 */
				
				function wploti_mr_delete_ak ( ak_id, ak_name ) {

					wploti_confirm.open({

						message: '<?php esc_html_e( "You are about to delete this Access Key:" , "maintenance-coming-soon-redirect-animation" ); ?>\n\n\'' + ak_name + '\'\n\n',
						onok: () => {
								// prepare ajax data
								var data = {
									action:		'wploti_mr_delete_ak',
									security:		'<?php  echo esc_attr($wploti_ajax_nonce); ?>',
									wploti_mr_ak_id:	ak_id
								};

								// set section to loading img								
								var img_url = '<?php echo esc_attr(plugins_url( 'images/ajax_loader_16x16.gif', __FILE__ )); ?>';
								<?php // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
								jQuery( '#wploti_mr_ak_tbl_container' ).html('<img src="' + img_url + '">');

								// send ajax request
								jQuery.post( ajaxurl, data, function(response) {
									jQuery('#wploti_mr_ak_tbl_container').html( response );
								});
						}
					})
				}
				
				
				/**
				 * (js) re-send Access Key
				 *
				 * @since 1.0.0
				 * @param ak_id number
				 * @param ak_name string
				 * @param ak_email string
				 * @return void
				 */
				
				function wploti_mr_resend_ak ( ak_id, ak_name, ak_email ) {
					
					wploti_confirm.open({
						message : '<?php esc_html_e( "You are about to resend an Access Key link to" , "maintenance-coming-soon-redirect-animation" ); ?> <b>' + ak_email + '</b> !<br> <?php esc_html_e( "If you do not see the email in a few secondes," , "maintenance-coming-soon-redirect-animation") ?> <br> <?php esc_html_e("Please check your “junk mail” or “spam” folder." , "maintenance-coming-soon-redirect-animation" ) ?> \n\n',
						onok: () => {
							// prepare ajax data
							var data = {
								action:		'wploti_mr_resend_ak',
								security:		'<?php  echo esc_attr($wploti_ajax_nonce); ?>',
								wploti_mr_ak_id:	ak_id
							};
							
							// send ajax request
							jQuery.post( ajaxurl, data, function(response) {
								if( response == 'SEND_SUCCESS' ){
									wploti_alert( '<?php esc_html_e( "Notification Sent." , "maintenance-coming-soon-redirect-animation" ); ?>','' );
								}else{
									wploti_alert( '<?php esc_html_e( "Notification Failure. Please check your server settings." , "maintenance-coming-soon-redirect-animation" ); ?>','' );
								}
							});
						}
					})
					
				}

				/**
				 * (js) RESET SETTINGS
				 *
				 * @since 1.0.0
				 * @return void
				 */

				jQuery('.wploti_reset_settings').click(function() {
					var security = jQuery(this).data('security');
					wploti_confirm.open({						
						message: '<?php esc_html_e( "Please Be careful ! " , "maintenance-coming-soon-redirect-animation" ) . '<br><br>' . esc_html_e( "Please Be careful ! " , "maintenance-coming-soon-redirect-animation" ) ?>',
						onok: () => {
							jQuery.ajax({
								url: ajaxurl,
								data: {
									action: 'wploti_reset_settings',
									security: security,
								},
								type: 'post',
								success: function(result, textstatus) {
									window.location.reload(true);
								},
								error: function(result) {
								},
							})
						}
					})
				})
			
			</script>
			
			<!-- **************  JS  ************** -->

			<div class="wrap">
					<h2></h2>
					<div class="wploti-head">
						<div class="wploti_animation_state">
							<lottie-player autoplay="true" loop src="<?php echo esc_attr ( $this->wploti_active() == '1' ? IMG_path .'/green-on.json' : IMG_path .'/red-off.json'  )?>"  class="animation-state"></lottie-player>
						</div>
						<h1 class="big-title"><?php echo esc_html(get_admin_page_title() , "maintenance-coming-soon-redirect-animation"); ?></h1>				
					</div>
							
				<p><?php esc_html_e( "Make your website in maintenance mode in seconds with great looking animations and configure settings to allow specific users to bypass the maintenance mode functionality in order to preview the site prior to public launch. Any logged in user with WordPress administrator privileges will be allowed to view the site regardless of the settings below." , "maintenance-coming-soon-redirect-animation" ); ?></p>
				<?php if ( get_option('wploti_notes_notice') ) : ?>
				<div class=" notice-success is-dismissable" id="wploti_note_notice">
					<div class="notice-activation-text-wrapper">
						<div class="note_head">
						<?php // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
						<img class="alert-icon" src="<?php echo esc_attr ( IMG_path.'/alert-icon.png' )?>" alt="Alert Icon" />
							<h3 class="main_redirect_msg"><?php esc_html_e( "Notes : " , "maintenance-coming-soon-redirect-animation" )?></h3>
						</div>
						<ul class="note_text">
							<li><?php esc_html_e( "This plugin will override any other maintenance plugin you use ." , "maintenance-coming-soon-redirect-animation" ); ?></li>
							<li><?php esc_html_e( "All settings are auto-updated , you don't need to save anything ." , "maintenance-coming-soon-redirect-animation" ); ?></li>
						</ul>
						<div class="wploti-leave-feedback">
							<div><a href="#dismiss" data-security="<?php echo esc_attr($wploti_ajax_nonce) ?>" name="wploti-activation-dismiss" class="wploti-note-dismiss"><?php esc_html_e('Dismiss' , "maintenance-coming-soon-redirect-animation"); ?></a></div>
						</div>
					</div>
				</div>
				<?php endif; ?>						
				<h3 class="big-title"><?php esc_html_e( "Enable Maintenance Mode:" , "maintenance-coming-soon-redirect-animation" ); ?></h3>
				<div class="enable-maintenance-mode">
					<div class="wploti-maintenance-toggle">
						<div class="toggle-wrapper">
							<input type="checkbox" data-security="<?php echo esc_attr($wploti_ajax_nonce) ?>" name="wploti_status" id="wploti-status" class="toggle-checkbox" <?php checked( '1', $this->wploti_active() );  ?>>
							<label for="wploti-status" class="toggle"><span class="toggle_handler"></span></label> 
						</div>
					</div>
				</div>

				<style>
					.wploti-maintenance-toggle .toggle:before {
						content: "<?php esc_html_e("Disabled", "maintenance-coming-soon-redirect-animation") ?>";
					}
					.wploti-maintenance-toggle .toggle:after {
						content: "<?php esc_html_e("Enabled", "maintenance-coming-soon-redirect-animation") ?>";
					}
				</style>


				<div id="wploti_main_options" style="display: <?php echo ( $this->wploti_active() == '1' ) ? 'block' : 'none'; ?> " >
							
					<?php 
				
					$tabs = array();
					$tabs[] = array('id' => 'header', 'icon' => 'dashicons-welcome-learn-more', 'class' => '', 'label' => esc_html__('Header Type', 'maintenance-coming-soon-redirect-animation'), 'callback' => array(__CLASS__, 'header_tab'));
					$tabs[] = array('id' => 'ip', 'icon' => 'dashicons-location', 'class' => '', 'label' => esc_html__('IP addresses', 'maintenance-coming-soon-redirect-animation'), 'callback' => array(__CLASS__, 'ip_tab'));
					$tabs[] = array('id' => 'keys', 'icon' => 'dashicons-admin-network', 'class' => '', 'label' => esc_html__('Access Keys', 'maintenance-coming-soon-redirect-animation'), 'callback' => array(__CLASS__, 'key_tab'));
					$tabs[] = array('id' => 'password', 'icon' => 'dashicons-lock', 'class' => '', 'label' => esc_html__('Password', 'maintenance-coming-soon-redirect-animation'), 'callback' => array(__CLASS__, 'password_tab'));
					$tabs[] = array('id' => 'animation', 'icon' => 'dashicons-welcome-view-site', 'class' => '', 'label' => esc_html__('Animation', 'maintenance-coming-soon-redirect-animation'), 'callback' => array(__CLASS__, 'animation_tab'));
					$tabs[] = array('id' => 'message', 'icon' => 'dashicons-admin-comments', 'class' => '', 'label' => esc_html__('Message', 'maintenance-coming-soon-redirect-animation'), 'callback' => array(__CLASS__, 'message_tab'));
					$tabs[] = array('id' => 'extra', 'icon' => 'dashicons-awards', 'class' => '', 'label' => esc_html__('Extra', 'maintenance-coming-soon-redirect-animation'), 'callback' => array(__CLASS__, 'extra_tab'));
			
					$tabs = apply_filters('wploti_tabs', $tabs);
			
					echo '<div id="wploti_tabs" class="ui-tabs" style="display: none;">';
					echo '<ul class="wploti-main-tab">';
					foreach ($tabs as $tab) {
						if (!empty($tab['label'])) {
							echo '<li><a href="#' . esc_attr($tab['id']) . '" class="' . esc_attr($tab['class']) . '"><span class="icon"><span class="dashicons ' . esc_attr($tab['icon']) . '"></span></span><span class="label">' . esc_attr($tab['label']) . '</span></a></li>';
						}
					}
					echo '</ul>';
			
					foreach ($tabs as $tab) {
						if (is_callable($tab['callback'])) {
							echo '<div style="display: none;" id="' . esc_attr($tab['id']) . '">';
							call_user_func($tab['callback']);
							echo '</div>';
						}
					} // foreach
					echo '</div>'; // wploti_tabs
					
					?>

					<div class="submit">
						<?php wp_nonce_field( 'wploti_nonce' ); ?>
						<!-- <input type="submit" name="update_wp_maintenance_redirect_settings" class="wp-core-ui button-primary" value="<?php esc_html_e( 'Update Settings' , 'maintenance-coming-soon-redirect-animation' ); ?>" /> -->
						<input type="button" value="<?php esc_attr_e( 'Reset settings', 'maintenance-coming-soon-redirect-animation' ); ?>" class="button button-secondary wploti_reset_settings" data-security="<?php echo esc_attr($wploti_ajax_nonce) ; ?>" name="submit" />
					</div>

				</div>
					
			</div>
				
			<?php
	
			
		} // end function print_admin_page()


	} // end class wploti_maintenance_redirect
}

if (class_exists("wploti_maintenance_redirect")) {
	$wploti_maintenance_redirect = new wploti_maintenance_redirect();
}


if (!function_exists("wploti_maintenance_redirect_ap")) {

	/**
	 * (php) initialize the admin and users panel
	 *
	 * @since 1.0.0
	 * @return void
	 */

	function wploti_maintenance_redirect_ap() {
		if( current_user_can('manage_options') ) {
			global $wploti_maintenance_redirect;
			global $wploti_ajax_nonce; 
				 $wploti_ajax_nonce = wp_create_nonce( "wploti_nonce" ); 
			
			if( !isset($wploti_maintenance_redirect) ) return;

			
			if (function_exists('add_options_page')) {
				add_options_page( 
					__("Maintenance Redirect Options" , 'maintenance-coming-soon-redirect-animation'),
					__("Maintenance" , "maintenance-coming-soon-redirect-animation"), 
						'manage_options', 
						'wploti-settings', 
						array( $wploti_maintenance_redirect, 'print_admin_page' ));
			}
		}
	}
}



// actions and filters	

if( isset( $wploti_maintenance_redirect ) ) {
	//global constants

	define('WPLOTI_VERSION','2.4.0');
	define('wploti_icon', 'data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHhtbG5zOnhsaW5rPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rIiBpZD0ic3ZnIiB2ZXJzaW9uPSIxLjEiIHdpZHRoPSI0MDAiIGhlaWdodD0iMjg4LjE4MjM2MzUyNzI5NDU3IiB2aWV3Qm94PSIwLCAwLCA0MDAsMjg4LjE4MjM2MzUyNzI5NDU3Ij48ZyBpZD0ic3ZnZyI+PHBhdGggaWQ9InBhdGgwIiBkPSJNMTQuOTk3IDAuMzQ0IEMgNy40NzkgMi4yNDcsMS43NzMgOC4xMzQsMC4zMzEgMTUuNDc3IEMgLTAuMjkzIDE4LjY1MSwtMC4xNDcgMTk5LjExNSwwLjQ4MiAyMDIuMTcwIEMgMi40MDggMjExLjUzMCw5LjM3NyAyMTkuODY3LDE3Ljk5NiAyMjMuMTIxIEMgMTkuNTE0IDIyMy42OTQsMjAuOTcyIDIyNC4yNTksMjEuMjM2IDIyNC4zNzUgQyAyMi4zMTYgMjI0Ljg1MiwyNS4yNzAgMjI1LjA3MywzMy41OTMgMjI1LjMwMiBDIDM4LjQxMCAyMjUuNDM0LDQ0LjAyNSAyMjUuNjA1LDQ2LjA3MSAyMjUuNjgyIEMgNDguMTE2IDIyNS43NTksNTUuODM3IDIyNS45MTgsNjMuMjI3IDIyNi4wMzYgQyA3MC42MTggMjI2LjE1NSw4MC41NTIgMjI2LjM2Miw4NS4zMDMgMjI2LjQ5OCBDIDkwLjA1NCAyMjYuNjMzLDEwMC41ODIgMjI2Ljg1OCwxMDguNjk4IDIyNi45OTggQyAxMTYuODE1IDIyNy4xMzgsMTI4LjM2OCAyMjcuMzUyLDEzNC4zNzMgMjI3LjQ3NSBDIDE0MC4zNzggMjI3LjU5NywxNTEuMjU3IDIyNy43NzIsMTU4LjU0OCAyMjcuODY0IEwgMTcxLjgwNiAyMjguMDMxIDE3MS44MDYgMjI5LjAyOCBDIDE3MS44MDYgMjI5LjU3NywxNzEuNzAxIDIzMC42MTMsMTcxLjU3MyAyMzEuMzMwIEMgMTcxLjQ0NSAyMzIuMDQ3LDE3MS4yMjMgMjMzLjc2NywxNzEuMDgwIDIzNS4xNTMgQyAxNzAuNTM5IDI0MC4zOTUsMTcwLjExNyAyNDQuMjQ2LDE2OS43NzYgMjQ3LjAzMSBDIDE2OS41ODIgMjQ4LjYxNCwxNjkuMjU5IDI1MS40NzYsMTY5LjA1NyAyNTMuMzg5IEMgMTY4Ljg1NiAyNTUuMzAzLDE2OC41ODQgMjU3LjY3OCwxNjguNDU0IDI1OC42NjggQyAxNjguMzIzIDI1OS42NTgsMTY4LjE4NyAyNjEuMTQyLDE2OC4xNTEgMjYxLjk2NiBDIDE2OC4xMTYgMjYyLjc5MCwxNjcuOTk1IDI2My40NjUsMTY3Ljg4MiAyNjMuNDY2IEMgMTY3Ljc3MCAyNjMuNDY3LDE2Ny43MTcgMjYzLjk1MywxNjcuNzY0IDI2NC41NDcgQyAxNjcuODExIDI2NS4xNDEsMTY3Ljc1NyAyNjUuNjI3LDE2Ny42NDQgMjY1LjYyNyBDIDE2Ny41MzEgMjY1LjYyNywxNjcuNDc3IDI2Ni4xMTMsMTY3LjUyNCAyNjYuNzA3IEMgMTY3LjU3MSAyNjcuMzAxLDE2Ny41MzcgMjY3Ljc4NiwxNjcuNDQ3IDI2Ny43ODYgQyAxNjcuMzU4IDI2Ny43ODYsMTY3LjIwOCAyNjguODY2LDE2Ny4xMTUgMjcwLjE4NiBDIDE2Ni45NDYgMjcyLjU3MiwxNjYuOTQxIDI3Mi41ODUsMTY2LjMxNiAyNzIuNTg2IEMgMTYxLjA1MSAyNzIuNTg5LDE0Ny45NDkgMjczLjE2MCwxNDYuOTA2IDI3My40MzIgQyAxNDMuODU0IDI3NC4yMjcsMTQyLjkwMSAyNzUuNTIzLDE0Mi4wMzQgMjgwLjA1OSBDIDE0MS44NzQgMjgwLjg5NywxNDEuNjYxIDI4MS42OTIsMTQxLjU2MCAyODEuODI0IEMgMTQwLjI3NiAyODMuNTEzLDE0MS44ODIgMjg3LjU0MiwxNDQuMDUwIDI4OC4wNjkgQyAxNDQuMzM2IDI4OC4xMzksMTczLjAyMyAyODguMTY2LDIwNy43OTggMjg4LjEyOSBMIDI3MS4wMjYgMjg4LjA2MiAyNzEuOTg5IDI4Ny41NDcgQyAyNzQuMTM4IDI4Ni4zOTUsMjc1LjA1MSAyODQuNzI0LDI3NC41NTkgMjgyLjgzOSBDIDI3NC40MTMgMjgyLjI4MCwyNzQuMDg1IDI4MS4wMTEsMjczLjgzMCAyODAuMDE3IEMgMjczLjU3NiAyNzkuMDI0LDI3My4wMTcgMjc3LjA4MCwyNzIuNTg5IDI3NS42OTggQyAyNzAuNjQzIDI2OS40MDgsMjcwLjg5MiAyNjkuNTQ0LDI2MS41NDggMjY5LjcwMiBDIDI1Ny43ODYgMjY5Ljc2NiwyNTMuNzkzIDI2OS44MjAsMjUyLjY3MiAyNjkuODIyIEwgMjUwLjYzNiAyNjkuODI2IDI1MC4yMjMgMjY3LjMwNyBDIDI0OS45OTcgMjY1LjkyMSwyNDkuNzAzIDI2NC4wODUsMjQ5LjU3MSAyNjMuMjI3IEMgMjQ5LjMzNyAyNjEuNzEzLDI0OS4xNTIgMjYwLjYxOSwyNDguMTU0IDI1NC44MjkgQyAyNDcuODkzIDI1My4zMTEsMjQ3LjU1NyAyNTEuMzE0LDI0Ny40MDggMjUwLjM5MCBDIDI0Ny4yNTkgMjQ5LjQ2NiwyNDYuOTE5IDI0Ny40MTUsMjQ2LjY1NCAyNDUuODMxIEMgMjQ2LjM4OCAyNDQuMjQ3LDI0Ni4wNzIgMjQyLjM1OCwyNDUuOTUxIDI0MS42MzIgQyAyNDUuODMxIDI0MC45MDYsMjQ1LjYzMyAyMzkuNzcyLDI0NS41MTEgMjM5LjExMiBDIDI0NS4zODggMjM4LjQ1MiwyNDUuMTYzIDIzNy4xMDMsMjQ1LjAwOSAyMzYuMTEzIEMgMjQ0Ljg1NiAyMzUuMTIzLDI0NC42MzAgMjMzLjc3MywyNDQuNTA3IDIzMy4xMTMgQyAyNDQuMzg0IDIzMi40NTQsMjQ0LjIxNSAyMzEuMzY0LDI0NC4xMzIgMjMwLjY5MiBMIDI0My45ODAgMjI5LjQ3MSAyNDcuNzg1IDIyOS4zMTcgQyAyNDkuODc3IDIyOS4yMzIsMjU0LjgyOSAyMjkuMjY4LDI1OC43ODggMjI5LjM5NyBDIDI2Ny43MjcgMjI5LjY4OCwzMjQuMjQ1IDIzMC4xMDksMzUzLjY4OSAyMzAuMTAzIEMgMzc2Ljk0NCAyMzAuMDk4LDM3OC41NDkgMjMwLjAzMywzODEuNjk0IDIyOC45NTcgQyAzODIuNDU4IDIyOC42OTYsMzgzLjczMSAyMjguMjkxLDM4NC41MjMgMjI4LjA1OCBDIDM5MS4yMDQgMjI2LjA5MCwzOTYuNTkwIDIyMC41NzgsMzk4LjIyMyAyMTQuMDM3IEMgMzk4Ljc1NCAyMTEuOTExLDM5OS4wMjEgMjA2LjM5MiwzOTkuMjg2IDE5Mi4wOTkgQyAzOTkuNDIzIDE4NC43MTgsMzk5LjY0MiAxNzguMjM5LDM5OS43NzIgMTc3LjcwMiBDIDQwMC4wNzAgMTc2LjQ4MCwzOTkuODkyIDk1Ljk0NiwzOTkuNTY3IDg0LjcwMyBDIDM5OS4yOTEgNzUuMTI5LDM5OC44ODcgNjUuNjk5LDM5OC41NzUgNjEuNTQ4IEMgMzk4LjQ0NiA1OS44MzIsMzk4LjI3MyA1Ni45MTcsMzk4LjE5MCA1NS4wNjkgQyAzOTcuOTE0IDQ4LjkzMSwzOTYuMjY0IDMxLjM5NywzOTUuNTQ4IDI2Ljk5NSBDIDM5NS4zNTUgMjUuODA3LDM5NS4wODMgMjQuMDc5LDM5NC45NDMgMjMuMTU1IEMgMzkzLjcwNiAxNC45NjgsMzg4LjA4NSA4LjcxNywzNzkuNzI0IDYuMjI5IEMgMzc4LjIzMCA1Ljc4NSwzNjQuNTIxIDUuMjA1LDM0Ni4wMTEgNC44MDMgQyAzMzkuNjEwIDQuNjY0LDMzMS40NTggNC40NDYsMzI3Ljg5NCA0LjMxOSBDIDMyNC4zMzEgNC4xOTIsMzEyLjY2OSAzLjkyOSwzMDEuOTgwIDMuNzMzIEMgMjkxLjI5MCAzLjUzOCwyNzkuNTc0IDMuMjY3LDI3NS45NDUgMy4xMzIgQyAyNzIuMzE2IDIuOTk3LDI2NS4xODkgMi44MjUsMjYwLjEwOCAyLjc1MCBDIDI1MS4zNjIgMi42MjAsMjI5LjIzNSAyLjE2MiwyMDkuMTE4IDEuNjk1IEMgMTk4LjM4OSAxLjQ0NSwxNzUuMDU2IDEuMDQwLDE1MS41MzAgMC42OTQgQyAxNDIuMjkyIDAuNTU4LDEyOC40NzAgMC4zNTQsMTIwLjgxNiAwLjI0MSBDIDk3LjgxMiAtMC4xMDAsMTYuNDM2IC0wLjAyMCwxNC45OTcgMC4zNDQgTTUwLjk5MCAxNy43NTggQyA3Ny40ODcgMTguMzc2LDgyLjc2MiAxOC40ODYsOTAuOTQyIDE4LjU5NiBDIDk1Ljc1OSAxOC42NjEsMTAwLjc4MCAxOC43NjcsMTAyLjEwMCAxOC44MzEgQyAxMDMuNDE5IDE4Ljg5NiwxMDcuMzA3IDE5LjAxMCwxMTAuNzM4IDE5LjA4NCBDIDE0Ny4yODMgMTkuODgwLDE3NC4zMTUgMjAuNDMwLDE4Ni45MjMgMjAuNjM1IEMgMjA3LjQxOSAyMC45NjksMjM2LjMzMCAyMS41MzEsMjU1LjkwOSAyMS45NzYgQyAyNjQuNzUxIDIyLjE3NywyNzUuNjAzIDIyLjM5OCwyODAuMDI0IDIyLjQ2OCBDIDI4NC40NDUgMjIuNTM4LDI4OC44MTggMjIuNjM0LDI4OS43NDIgMjIuNjgyIEMgMjkwLjY2NiAyMi43MjksMjk2LjI4MSAyMi44MjEsMzAyLjIyMCAyMi44ODUgQyAzMDguMTU4IDIyLjk0OSwzMTMuMTc5IDIzLjA2MSwzMTMuMzc3IDIzLjEzMyBDIDMxMy41NzUgMjMuMjA2LDMxOC4yMTggMjMuMzIwLDMyMy42OTUgMjMuMzg2IEMgMzM5LjY3NCAyMy41NzksMzYxLjEzNSAyNC4xODAsMzYyLjQzNiAyNC40NzIgQyAzNjUuNTc4IDI1LjE3NywzNjguMjcyIDI3LjQxOSwzNjkuNTU2IDMwLjM5NSBMIDM3MC4zNjYgMzIuMjc0IDM3MC4zNjYgNTUuOTA5IEMgMzcwLjM2NiA5OC4xNjcsMzY5Ljk3OSAxODYuNzA5LDM2OS43NzggMTkwLjQwMiBDIDM2OS41NzAgMTk0LjIxOCwzNjkuMzk5IDE5NS4zOTEsMzY5LjAzMSAxOTUuNTIxIEMgMzY4LjkwNiAxOTUuNTY2LDM2OC44NTkgMTk1LjY5MiwzNjguOTI3IDE5NS44MDIgQyAzNjguOTk1IDE5NS45MTIsMzY4Ljg4MCAxOTYuMTczLDM2OC42NzEgMTk2LjM4MSBDIDM2OC40NjMgMTk2LjU5MCwzNjguMzYzIDE5Ni43NjEsMzY4LjQ0OSAxOTYuNzYxIEMgMzY4LjY1NCAxOTYuNzYxLDM2Ny43MDUgMTk4LjMxMywzNjYuNzc5IDE5OS40OTMgQyAzNjUuOTc1IDIwMC41MTcsMzYzLjE1NSAyMDIuNzU5LDM2Mi42NzIgMjAyLjc1OSBDIDM2Mi41MDQgMjAyLjc1OSwzNjIuMzIzIDIwMi44OTQsMzYyLjI2OCAyMDMuMDU5IEMgMzYyLjE5MSAyMDMuMjkwLDM2MC42MzkgMjAzLjM1OSwzNTUuNTg5IDIwMy4zNTkgQyAzNDkuMDE2IDIwMy4zNTksMzQ0LjE3NyAyMDMuMzAzLDI5My45NDEgMjAyLjY0MSBDIDI0OC44NDMgMjAyLjA0NywyMDkuMDI3IDIwMS41ODYsMTkwLjQwMiAyMDEuNDQzIEMgMTgwLjYzNiAyMDEuMzY4LDE1Ni40NDkgMjAxLjEwMCwxMzYuNjUzIDIwMC44NDcgQyAxMTYuODU3IDIwMC41OTMsOTQuNjY3IDIwMC4zMjUsODcuMzQzIDIwMC4yNTEgQyA4MC4wMTggMjAwLjE3Niw3Mi45NDUgMjAwLjA2NCw3MS42MjYgMjAwLjAwMiBDIDcwLjMwNiAxOTkuOTM5LDYyLjE1NCAxOTkuODMxLDUzLjUwOSAxOTkuNzYyIEMgMjkuNDg3IDE5OS41NzAsMjkuMDU5IDE5OS41MzksMjUuNDM1IDE5Ny43NjkgQyAyMi4zODkgMTk2LjI4MiwyMC4zMjUgMTkzLjY4MywxOS40ODkgMTkwLjI4MiBDIDE4Ljk3MSAxODguMTc0LDE4LjY3MiA3Mi44MTUsMTkuMDkyIDM3LjU1MiBMIDE5LjIzNiAyNS41NTUgMjAuMTcxIDIzLjU5OSBDIDIxLjI1NSAyMS4zMzIsMjIuOTI5IDE5LjY3NSwyNS4zMzcgMTguNDg2IEMgMjcuOTYwIDE3LjE5MCwyNy4zNTcgMTcuMjA3LDUwLjk5MCAxNy43NTggTTE4NC4xNDQgMzEuMjk2IEMgMTgyLjcyMCAzMS43MzYsMTgxLjc3OSAzMi41MjYsMTgxLjAxNCAzMy45MjIgTCAxODAuMzQwIDM1LjE1MyAxODAuMTkwIDQ0Ljc1MSBDIDE3OS45ODQgNTcuOTU2LDE3OS44NjAgNTguMjE1LDE3Mi4yODYgNjEuMTgwIEMgMTY0Ljc5MyA2NC4xMTMsMTY0LjUyMyA2NC4wMTAsMTU1LjU2MiA1NC44MDEgQyAxNDguNDA4IDQ3LjQ0OSwxNDguMTgwIDQ3LjI2NCwxNDYuMDM3IDQ3LjA5MiBDIDE0My41NDAgNDYuODkyLDE0My4wNjcgNDcuMjE1LDEzNi4zMTQgNTMuNzUyIEMgMTI2LjgxMCA2Mi45NTIsMTI2LjgwNiA2Mi42NDUsMTM2LjU3MCA3Mi42ODQgQyAxNDUuNjc4IDgyLjA0OCwxNDUuODE0IDgyLjQzNiwxNDIuNTcwIDg5Ljc0MiBDIDE0MC41NDUgOTQuMzAxLDEzOS43OTUgOTUuMzM3LDEzNy42OTMgOTYuNDc3IEwgMTM2LjE3MyA5Ny4zMDEgMTI2LjA5NSA5Ny4zMDEgTCAxMTYuMDE3IDk3LjMwMSAxMTQuODcyIDk3Ljk3NCBDIDExMi4yODkgOTkuNDkyLDExMi4wNjEgMTAwLjU2NCwxMTIuMDU5IDExMS4xNjUgQyAxMTIuMDU3IDEyMy41MjIsMTExLjUzNyAxMjMuMDU2LDEyNS41NTYgMTIzLjI5NSBMIDEzNC45ODQgMTIzLjQ1NSAxMzYuNTg2IDEyNC4yNDQgQyAxMzkuMDg3IDEyNS40NzYsMTM5Ljc2NyAxMjYuNDI4LDE0MS43MDkgMTMxLjQxOSBDIDE0NC44MzYgMTM5LjQ1NywxNDQuODYyIDEzOS40MDQsMTMxLjE3MSAxNTIuMzg0IEMgMTI1Ljk5MyAxNTcuMjkzLDEyNi4zNDQgMTU4LjY1NCwxMzUuMDYyIDE2Ny40ODIgQyAxNDQuMTQ3IDE3Ni42ODEsMTQzLjU1NiAxNzYuNjk4LDE1My42MTMgMTY2Ljk2NCBDIDE2Mi43MDggMTU4LjE2MiwxNjIuOTExIDE1OC4wOTMsMTcwLjM0MCAxNjEuMjY1IEMgMTc1LjE5MyAxNjMuMzM4LDE3Ni40ODUgMTY0LjI5MywxNzcuNjA3IDE2Ni42NDEgTCAxNzguMjk4IDE2OC4wODYgMTc4LjI1OSAxNzcuNjkyIEMgMTc4LjIwMiAxOTEuODQ0LDE3Ny42MzQgMTkxLjIzMSwxOTEuMDE0IDE5MS40MjYgQyAyMDQuNDc3IDE5MS42MjIsMjAzLjk4NCAxOTIuMTEyLDIwNC4yMzEgMTc4LjI4NCBDIDIwNC40MTEgMTY4LjIyMywyMDQuNDM3IDE2OC4wMjIsMjA1Ljg3NiAxNjUuOTMxIEMgMjA2Ljg0OSAxNjQuNTE3LDIwOC4xMDEgMTYzLjcwNCwyMTEuMjE4IDE2Mi40NjIgTCAyMTMuNzk5IDE2MS40MzQgMjEyLjYxNSAxNjAuMDgxIEMgMjExLjk2NCAxNTkuMzM3LDIxMC4xNDMgMTU3LjQ0OSwyMDguNTY5IDE1NS44ODYgTCAyMDUuNzA2IDE1My4wNDQgMjAzLjI2NyAxNTMuNjU5IEMgMTY4LjMwMiAxNjIuNDc3LDEzOC4xNTMgMTI3LjY3NiwxNTIuMjIyIDk0LjczOCBDIDE2NS45MTggNjIuNjc0LDIwOS40ODMgNTguODY5LDIyOC42NjQgODguMDYyIEMgMjM1LjY3MCA5OC43MjYsMjM3LjY4OCAxMTQuMzE5LDIzMy40ODggMTI1LjMzOCBDIDIzMy4yMzUgMTI2LjAwMiwyMzMuMzMzIDEyNi4xMjgsMjM3LjIxOSAxMzAuMTM3IEMgMjM5LjQxMyAxMzIuNDAxLDI0MS4yODIgMTM0LjI4MiwyNDEuMzcxIDEzNC4zMTcgQyAyNDEuNDU5IDEzNC4zNTEsMjQyLjA1MyAxMzMuMTQxLDI0Mi42OTEgMTMxLjYyNiBDIDI0My45MDIgMTI4Ljc0OSwyNDQuOTQwIDEyNy4yNzQsMjQ2LjQwNiAxMjYuMzUyIEMgMjQ4LjI4MCAxMjUuMTczLDI0OC41NjAgMTI1LjE0OCwyNTguNTQ4IDEyNS4yMjcgQyAyNzIuNzQ1IDEyNS4zMzksMjcyLjI2MyAxMjUuODAyLDI3Mi4zODQgMTExLjkzOCBDIDI3Mi40OTIgOTkuNTQzLDI3Mi4zNjcgOTkuMzg0LDI2Mi41MDcgOTkuMzMzIEMgMjQ1Ljg3MyA5OS4yNDcsMjQ1LjcwNSA5OS4xNzgsMjQyLjQ3MiA5MS4xODggQyAyMzkuNDU1IDgzLjczNSwyMzkuNTc1IDgzLjQxMywyNDguNjQyIDc0LjY0MyBDIDI1NS40NDQgNjguMDYyLDI1Ni4xNjEgNjcuMjI4LDI1Ni40MDQgNjUuNjEwIEMgMjU2LjgxNiA2Mi44NjIsMjU2LjUwNCA2Mi4zODUsMjQ5LjY5OSA1NS4zNDYgQyAyNDAuNTQzIDQ1Ljg3NCwyNDAuODU5IDQ1Ljg2NCwyMzAuNTEzIDU1LjkyMyBDIDIyMS40MjAgNjQuNzYzLDIyMC45MDQgNjQuOTQ2LDIxMy44MTUgNjEuODUxIEMgMjA2LjAxNiA1OC40NDUsMjA1Ljk4NiA1OC4zODAsMjA2LjE4NyA0NS4zNTcgQyAyMDYuNDEyIDMwLjgxNSwyMDYuODczIDMxLjMyNCwxOTMuMjgxIDMxLjExOCBDIDE4Ny4xMDAgMzEuMDI0LDE4NC44ODYgMzEuMDY3LDE4NC4xNDQgMzEuMjk2IE0xOTEuNjk1IDgwLjEzMyBDIDE4OS45NjcgODAuMzExLDE4Ni4yOTggODEuMDI5LDE4NS44OTggODEuMjY3IEMgMTg1LjUzOSA4MS40ODIsMTg2Ljg2NyA4Mi45NzQsMTkyLjY5NyA4OC45MDIgQyAxOTguMDc1IDk0LjM3MiwxOTkuODQyIDk2LjQ0MCwyMDAuOTA2IDk4LjUxMiBDIDIwNy4yNTggMTEwLjg3NywxOTIuNzg5IDEyNS42ODcsMTgwLjA0NCAxMTkuODY3IEMgMTc2LjkxNyAxMTguNDM5LDE3NS41MTQgMTE3LjE4MiwxNjguMTE4IDEwOS4xNzggQyAxNjUuNDk2IDEwNi4zNDEsMTYzLjE3NSAxMDMuOTkyLDE2Mi45NTkgMTAzLjk1OSBDIDE2MS40MzMgMTAzLjcyNiwxNjAuNTk5IDEwNy42MzksMTYwLjg1MCAxMTMuODU3IEMgMTYxLjIwNyAxMjIuNjc1LDE2NC40NDggMTI5LjYzMiwxNzEuMTI3IDEzNS45MjAgQyAxNzkuODQzIDE0NC4xMjUsMTkwLjEzOCAxNDYuNTU5LDIwMi4xNjAgMTQzLjI1OSBDIDIwNy4yODggMTQxLjg1MSwyMDUuMDI1IDE0MC4xNDIsMjI2LjY1OCAxNjEuNzYxIEMgMjM2LjgzMyAxNzEuOTI5LDI0NS42NDUgMTgwLjU2NSwyNDYuMjQwIDE4MC45NTIgQyAyNTQuOTc3IDE4Ni42MzUsMjY2LjUwMCAxNzYuNDc4LDI2MS45MzggMTY3LjExNSBDIDI2MS4wMjYgMTY1LjI0NSwyNjAuNjMyIDE2NC44MzAsMjQyLjM0OCAxNDYuNDkxIEMgMjMyLjA4NSAxMzYuMTk3LDIyMy41NjkgMTI3LjUzOSwyMjMuNDIyIDEyNy4yNTAgQyAyMjIuOTYwIDEyNi4zNDMsMjIzLjEyOCAxMjQuNzg0LDIyMy45NzIgMTIyLjE1OCBDIDIzMC45NjEgMTAwLjM5MSwyMTMuNjY0IDc3Ljg2OSwxOTEuNjk1IDgwLjEzMyBNMjUyLjc4OSAxNjYuMjU4IEMgMjU3LjY3NCAxNjguNDk4LDI1Ni4zMDcgMTc1LjY0NSwyNTAuOTk0IDE3NS42NDUgQyAyNDYuNTQxIDE3NS42NDUsMjQ0LjM3NSAxNzAuNDk2LDI0Ny41MDggMTY3LjM1OCBDIDI0OS4wMjEgMTY1Ljg0MiwyNTAuOTkwIDE2NS40MzIsMjUyLjc4OSAxNjYuMjU4IE0yMC42NjYgMjA2Ljg5MSBDIDIwLjc1OSAyMDcuMDQwLDIwLjg4NSAyMDcuMDE4LDIwLjk5OSAyMDYuODM0IEMgMjEuMTQxIDIwNi42MDMsMjIuOTQ1IDIwNi41NzMsMjkuMTI3IDIwNi42OTcgQyAzMy40OTcgMjA2Ljc4NCw1Mi43MjkgMjA3LjEyMiw3MS44NjYgMjA3LjQ0NyBDIDkxLjAwMiAyMDcuNzcyLDEyNS4zOTMgMjA4LjM2MiwxNDguMjkwIDIwOC43NTkgQyAxNzEuMTg4IDIwOS4xNTYsMjE1LjA4MSAyMDkuOTEwLDI0NS44MzEgMjEwLjQzNSBDIDI3Ni41ODEgMjEwLjk1OSwzMjEuMDY4IDIxMS43MTksMzQ0LjY5MSAyMTIuMTI0IEMgMzY5LjcxMCAyMTIuNTUyLDM4Ny44NzQgMjEyLjk1NSwzODguMTk2IDIxMy4wODkgQyAzODkuODQwIDIxMy43NzMsMzg4LjM1OSAyMTMuODY3LDM3OC41NDggMjEzLjcwNiBDIDM0NS4xNDkgMjEzLjE1NiwyODEuMDY0IDIxMi4wODksMjY4Ljg2NiAyMTEuODc5IEMgMjEyLjcwMCAyMTAuOTE2LDE1Mi4yNDggMjA5Ljg5OSw5Ny42NjAgMjA4Ljk5OSBDIDM4Ljc0OCAyMDguMDI3LDEyLjQwMCAyMDcuNTM5LDEyLjIwMSAyMDcuNDE2IEMgMTIuMDg5IDIwNy4zNDcsMTEuOTk4IDIwNy4wNjgsMTEuOTk4IDIwNi43OTUgQyAxMS45OTggMjA2LjI1MCwyMC4zMjAgMjA2LjM0MiwyMC42NjYgMjA2Ljg5MSBNMjA0Ljc0NiAyMTMuMDkyIEMgMjA3LjA1NSAyMTMuNzMzLDIwOC4zNjYgMjE2LjE0OCwyMDcuNjgxIDIxOC41MDIgQyAyMDYuNTg1IDIyMi4yNzMsMjAxLjQ1NyAyMjIuODAxLDE5OS43MDAgMjE5LjMyMyBDIDE5Ny45MDQgMjE1Ljc2OCwyMDAuOTI4IDIxMi4wMzIsMjA0Ljc0NiAyMTMuMDkyIE0yMDEuOTIwIDIxNC40ODQgQyAyMDAuMTQ4IDIxNS41NzgsMTk5LjcyMCAyMTcuMDM4LDIwMC42NDUgMjE4LjgzNCBDIDIwMS44MTUgMjIxLjEwNywyMDQuODQzIDIyMS4yNDcsMjA2LjI5MyAyMTkuMDk1IEMgMjA4LjE4NiAyMTYuMjg2LDIwNC43OTQgMjEyLjcwOCwyMDEuOTIwIDIxNC40ODQgIiBzdHJva2U9Im5vbmUiIGZpbGw9IiNhN2FhYWQiIGZpbGwtcnVsZT0iZXZlbm9kZCIvPjwvZz48L3N2Zz4=');
	define('wploti_animation_dir', plugin_dir_url( __FILE__ ) .'animations/');
	define('IMG_path', plugin_dir_url( __FILE__ ) .'images');
	define( 'wploti_admin_url', admin_url().'admin.php?page=wploti-settings');

	// notice_dismiss
	add_action('wp_ajax_wploti_ajax_dismiss_activation_notice', array( $wploti_maintenance_redirect, 'wploti_ajax_dismiss_activation_notice' ) );
	add_action('wp_ajax_wploti_ajax_dismiss_notes_notice', array( $wploti_maintenance_redirect, 'wploti_ajax_dismiss_notes_notice' ) );
	// animation_select
	add_action('wp_ajax_wploti_animation_select', array( $wploti_maintenance_redirect, 'animation_select' ) );
	add_action('wp_ajax_wploti_animation_ajax_load', array( $wploti_maintenance_redirect, 'load_animations' ) );
	// actions & filters
	add_action('admin_menu', 'wploti_maintenance_redirect_ap' );
	add_action('admin_menu',   array( $wploti_maintenance_redirect, 'wploti_maintenance_redirect_menu' ));
	add_action('send_headers',  array( $wploti_maintenance_redirect, 'process_redirect'), 0 );
	add_action('admin_notices', array( $wploti_maintenance_redirect, 'display_status_if_active' ) );
	//add_action( 'admin_notices', array( $wploti_maintenance_redirect, 'loti_notice' ) );
	add_action('wp_before_admin_bar_render', array( $wploti_maintenance_redirect, 'wploti_admin_bar' ) );
	//enqueue styles and scripts
	add_action('admin_enqueue_scripts', array( $wploti_maintenance_redirect ,'wploti_enqueue_style_and_script_admin' ) );	
	add_action('wp_enqueue_scripts', array( $wploti_maintenance_redirect ,'wploti_enqueue_style_and_script_public' ) );
	add_filter( 'body_class', array( $wploti_maintenance_redirect, 'add_maintenance_page_body_class' ) );
	add_action( 'wp_footer', array( $wploti_maintenance_redirect, 'render_custom_maintenance_access_panels' ) );
	// Priority -10 so this runs before process_redirect() (send_headers priority 0); the custom login URL only needs to win the race while maintenance mode is active.
	add_action( 'send_headers', array( $wploti_maintenance_redirect, 'render_custom_login_page' ), -10 );
	add_action( 'login_init', array( $wploti_maintenance_redirect, 'restrict_default_login_url' ) );
	//register script for translation
	add_action('admin_enqueue_scripts', array( $wploti_maintenance_redirect ,'wploti_translations_script' ) );	

	//add_filter('plugin_action_links_'.plugin_basename(__FILE__), array( $wploti_maintenance_redirect, 'plugin_settings_link' ) );
	add_filter('site_status_tests', array( $wploti_maintenance_redirect, 'wploti_add_site_health' ) );
	add_filter('admin_body_class',  array( $wploti_maintenance_redirect,'wploti_body_class' ) );
	add_filter('login_message',  array( $wploti_maintenance_redirect, 'login_message'));
	add_filter('upload_mimes',  array( $wploti_maintenance_redirect, 'wploti_mime_types'));
	add_filter( 'login_url', array( $wploti_maintenance_redirect, 'filter_login_url' ), 10, 3 );
	add_filter( 'site_url', array( $wploti_maintenance_redirect, 'filter_login_post_url' ), 10, 4 );
	add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array( $wploti_maintenance_redirect, 'wploti_action_links' ) );
	add_filter( 'plugin_row_meta', array( $wploti_maintenance_redirect, 'wploti_plugin_row_meta' ), 10, 2 );


	// ajax actions
	add_action('wp_ajax_wploti_toggle_activation', array( $wploti_maintenance_redirect, 'wploti_ajax_toggle_activation') );
	add_action('wp_ajax_wploti_header_type',  array( $wploti_maintenance_redirect, 'wploti_ajax_header_type') );
	add_action('wp_ajax_wploti_mr_add_ip',    array( $wploti_maintenance_redirect, 'add_new_ip'       ) );
	add_action('wp_ajax_wploti_mr_update_ip', array( $wploti_maintenance_redirect, 'update_ip'        ) );
	add_action('wp_ajax_wploti_mr_toggle_ip', array( $wploti_maintenance_redirect, 'toggle_ip_status' ) );
	add_action('wp_ajax_wploti_mr_delete_ip', array( $wploti_maintenance_redirect, 'delete_ip'        ) );
	add_action('wp_ajax_wploti_mr_add_ak',    array( $wploti_maintenance_redirect, 'add_new_ak'       ) );
	add_action('wp_ajax_wploti_save_password', array( $wploti_maintenance_redirect, 'save_password' ) );
	add_action('wp_ajax_wploti_mr_toggle_ak', array( $wploti_maintenance_redirect, 'toggle_ak_status' ) );
	add_action('wp_ajax_wploti_mr_delete_ak', array( $wploti_maintenance_redirect, 'delete_ak'        ) );
	add_action('wp_ajax_wploti_mr_resend_ak', array( $wploti_maintenance_redirect, 'resend_ak'        ) );
	add_action('wp_ajax_wploti_ajax_message', array( $wploti_maintenance_redirect, 'wploti_ajax_message') );
	add_action('wp_ajax_wploti_uploaded_animation_save', array( $wploti_maintenance_redirect, 'wploti_uploaded_animation_save_option') );
	add_action('wp_ajax_wploti_add_whitelisted_roles', array( $wploti_maintenance_redirect, 'wploti_add_whitelisted_roles_option') );
	add_action('wp_ajax_wploti_remove_whitelisted_roles', array( $wploti_maintenance_redirect, 'wploti_remove_whitelisted_roles_option') );
	add_action('wp_ajax_wploti_add_whitelisted_users', array( $wploti_maintenance_redirect, 'wploti_add_whitelisted_users_option') );
	add_action('wp_ajax_wploti_remove_whitelisted_users', array( $wploti_maintenance_redirect, 'wploti_remove_whitelisted_users_option') );
	add_action( 'wp_ajax_wploti_save_custom_login_url', array( $wploti_maintenance_redirect, 'save_custom_login_url' ) );
	add_action( 'wp_ajax_wploti_toggle_post_exclusion', array( $wploti_maintenance_redirect, 'toggle_post_exclusion' ) );
	add_action( 'wp_ajax_wploti_toggle_maintenance_post', array( $wploti_maintenance_redirect, 'toggle_maintenance_post' ) );
	
	// activation & deactivation 
	register_activation_hook( __FILE__, array( $wploti_maintenance_redirect, 'init' ) );
	register_deactivation_hook( __FILE__, array( $wploti_maintenance_redirect, 'wploti_deactivate' ) );

	// Reset Settings action
	add_action( 'wp_ajax_wploti_reset_settings', array( $wploti_maintenance_redirect, 'reset_plugin_settings' ) );

	// Translation
    //add_action( 'plugins_loaded', array( $wploti_maintenance_redirect, 'wploti_translation' ));
    add_action( 'init', array( $wploti_maintenance_redirect, 'init_hooks' ));
	add_action( 'init', array( $wploti_maintenance_redirect, 'register_post_type_row_actions' ), 99 );

	add_action('admin_head', array( $wploti_maintenance_redirect, 'add_site_favicon'));
	add_action('wp_default_scripts', array( $wploti_maintenance_redirect, 'remove_jquery_migrate_console') );
	

}
